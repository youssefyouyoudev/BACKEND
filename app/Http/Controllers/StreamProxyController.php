<?php

namespace App\Http\Controllers;

use App\Services\UrlSafetyService;
use App\Support\StreamUrl;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamProxyController extends Controller
{
    private const CHUNK_BYTES = 65536;
    private const SNIFF_BYTES = 4096;
    private const NO_DATA_TIMEOUT_SECONDS = 90;
    private const EMPTY_READ_SLEEP_MICROSECONDS = 250000;

    public function __construct(
        private readonly UrlSafetyService $urlSafetyService,
    ) {
    }

    public function __invoke(Request $request, string $encodedUrl): Response
    {
        $url = StreamUrl::decodeProxyUrl($encodedUrl);

        abort_if(
            $url === null || ! StreamUrl::hasValidProxySignature($url, (string) $request->query('sig')),
            Response::HTTP_FORBIDDEN
        );

        $this->urlSafetyService->assertSafeForImport($url);

        $headers = [
            'Accept' => '*/*',
            'User-Agent' => 'VLC/3.0.20 LibVLC/3.0.20',
            'Connection' => 'keep-alive',
        ];

        if ($request->headers->has('Range')) {
            $headers['Range'] = (string) $request->headers->get('Range');
        }

        if ($request->headers->has('Referer')) {
            $headers['Referer'] = (string) $request->headers->get('Referer');
        }

        Log::debug('[RiFiProxy] Opening upstream stream', [
            'url' => StreamUrl::masked($url),
            'range' => $headers['Range'] ?? null,
        ]);

        try {
            $upstream = (new Client([
                'timeout' => 0,
                'connect_timeout' => 15,
                'read_timeout' => 60,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => true,
                ],
                'stream' => true,
                'http_errors' => false,
                'headers' => $headers,
            ]))->request('GET', $url);
        } catch (GuzzleException $exception) {
            Log::warning('[RiFiProxy] Upstream connection failed', [
                'url' => StreamUrl::masked($url),
                'error' => $exception->getMessage(),
            ]);

            abort(Response::HTTP_BAD_GATEWAY, 'Stream upstream unavailable.');
        }

        $status = $upstream->getStatusCode();

        abort_if($status < 200 || $status >= 400, $status);

        $contentType = $upstream->getHeaderLine('Content-Type');
        $body = $upstream->getBody();
        $prefix = '';
        $isPlaylist = StreamUrl::isLikelyPlaylistUrl($url) || str_contains(strtolower($contentType), 'mpegurl');

        if (! $isPlaylist && ! StreamUrl::isLikelyMpegTsUrl($url, $contentType) && ! str_starts_with(strtolower($contentType), 'video/')) {
            $prefix = $this->safeRead($body, self::SNIFF_BYTES, $url, 8);
            $isPlaylist = str_starts_with(ltrim($prefix), '#EXTM3U');
        }

        if ($isPlaylist) {
            $playlist = $prefix.$this->readRemainingPlaylist($body, $url);
            $playlist = StreamUrl::rewritePlaylist($playlist, $url);

            return response($playlist, $status, [
                'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Access-Control-Allow-Origin' => '*',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        $responseHeaders = [
            'Content-Type' => StreamUrl::contentTypeFor($url, $contentType),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
            'X-Accel-Buffering' => 'no',
            'Accept-Ranges' => $upstream->getHeaderLine('Accept-Ranges') ?: 'bytes',
        ];

        foreach (['Content-Length', 'Content-Range'] as $header) {
            $value = $upstream->getHeaderLine($header);

            if ($value !== '') {
                $responseHeaders[$header] = $value;
            }
        }

        return new StreamedResponse(function () use ($body, $prefix, $url): void {
            $this->disableOutputBuffers();

            if ($prefix !== '') {
                $this->emitChunk($prefix);
            }

            $lastDataAt = microtime(true);

            while (! $body->eof()) {
                if (connection_aborted()) {
                    Log::debug('[RiFiProxy] Client disconnected while streaming', [
                        'url' => StreamUrl::masked($url),
                    ]);
                    break;
                }

                try {
                    $chunk = $body->read(self::CHUNK_BYTES);
                } catch (RuntimeException $exception) {
                    if ((microtime(true) - $lastDataAt) >= self::NO_DATA_TIMEOUT_SECONDS) {
                        Log::warning('[RiFiProxy] Closing stalled upstream after read timeout', [
                            'url' => StreamUrl::masked($url),
                            'idle_seconds' => self::NO_DATA_TIMEOUT_SECONDS,
                            'error' => $exception->getMessage(),
                        ]);
                        break;
                    }

                    usleep(self::EMPTY_READ_SLEEP_MICROSECONDS);
                    continue;
                }

                if ($chunk === '') {
                    if ((microtime(true) - $lastDataAt) >= self::NO_DATA_TIMEOUT_SECONDS) {
                        Log::warning('[RiFiProxy] Closing upstream after no data', [
                            'url' => StreamUrl::masked($url),
                            'idle_seconds' => self::NO_DATA_TIMEOUT_SECONDS,
                        ]);
                        break;
                    }

                    usleep(self::EMPTY_READ_SLEEP_MICROSECONDS);
                    continue;
                }

                $lastDataAt = microtime(true);
                $this->emitChunk($chunk);
            }
        }, $status, $responseHeaders);
    }

    private function safeRead(StreamInterface $body, int $length, string $url, int $maxIdleSeconds): string
    {
        $startedAt = microtime(true);

        while (! $body->eof()) {
            try {
                return $body->read($length);
            } catch (RuntimeException $exception) {
                if ((microtime(true) - $startedAt) >= $maxIdleSeconds) {
                    Log::debug('[RiFiProxy] Initial stream sniff timed out', [
                        'url' => StreamUrl::masked($url),
                        'error' => $exception->getMessage(),
                    ]);

                    return '';
                }

                usleep(self::EMPTY_READ_SLEEP_MICROSECONDS);
            }
        }

        return '';
    }

    private function readRemainingPlaylist(StreamInterface $body, string $url): string
    {
        $playlist = '';
        $lastDataAt = microtime(true);

        while (! $body->eof()) {
            try {
                $chunk = $body->read(self::CHUNK_BYTES);
            } catch (RuntimeException $exception) {
                if ((microtime(true) - $lastDataAt) >= 20) {
                    Log::warning('[RiFiProxy] Playlist read stopped after upstream pause', [
                        'url' => StreamUrl::masked($url),
                        'error' => $exception->getMessage(),
                    ]);
                    break;
                }

                usleep(self::EMPTY_READ_SLEEP_MICROSECONDS);
                continue;
            }

            if ($chunk === '') {
                if ((microtime(true) - $lastDataAt) >= 20) {
                    break;
                }

                usleep(self::EMPTY_READ_SLEEP_MICROSECONDS);
                continue;
            }

            $lastDataAt = microtime(true);
            $playlist .= $chunk;
        }

        return $playlist;
    }

    private function emitChunk(string $chunk): void
    {
        echo $chunk;

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        @flush();
    }

    private function disableOutputBuffers(): void
    {
        @set_time_limit(0);
        @ignore_user_abort(true);

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
    }
}

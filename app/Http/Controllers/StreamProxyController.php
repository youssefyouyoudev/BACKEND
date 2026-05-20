<?php

namespace App\Http\Controllers;

use App\Services\UrlSafetyService;
use App\Support\StreamUrl;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamProxyController extends Controller
{
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
            'Accept' => 'application/vnd.apple.mpegurl, application/x-mpegURL, video/mp2t, video/mp4, video/*, */*',
            'User-Agent' => 'VLC/3.0.20 LibVLC/3.0.20 RiFiMediaTV/1.0',
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

        $upstream = (new Client([
            'timeout' => 0,
            'connect_timeout' => 15,
            'read_timeout' => 30,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
            ],
            'stream' => true,
            'http_errors' => false,
            'headers' => $headers,
        ]))->request('GET', $url);

        $status = $upstream->getStatusCode();

        abort_if($status < 200 || $status >= 400, $status);

        $contentType = $upstream->getHeaderLine('Content-Type');
        $body = $upstream->getBody();
        $prefix = '';
        $isPlaylist = StreamUrl::isLikelyPlaylistUrl($url) || str_contains(strtolower($contentType), 'mpegurl');

        if (! $isPlaylist && ! StreamUrl::isLikelyMpegTsUrl($url, $contentType) && ! str_starts_with(strtolower($contentType), 'video/')) {
            $prefix = $body->read(4096);
            $isPlaylist = str_starts_with(ltrim($prefix), '#EXTM3U');
        }

        if ($isPlaylist) {
            $playlist = $prefix.(string) $body->getContents();
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

        return new StreamedResponse(function () use ($body, $prefix): void {
            $this->disableOutputBuffers();

            if ($prefix !== '') {
                echo $prefix;
                flush();
            }

            while (! $body->eof()) {
                echo $body->read(1024 * 64);
                flush();
            }
        }, $status, $responseHeaders);
    }

    private function disableOutputBuffers(): void
    {
        @set_time_limit(0);

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
    }
}

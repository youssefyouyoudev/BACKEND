<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelStream;
use App\Support\StreamUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StreamBridgeController extends Controller
{
    public function __invoke(Request $request, string $encodedUrl): Response
    {
        $url = StreamUrl::decodeProxyUrl($encodedUrl);

        $this->abortUnlessBridgeEnabled();
        $this->abortUnlessAllowedStreamUrl($url);
        $this->logBridgeAttempt($request, $url);

        return $this->bridge($url);
    }

    public function playChannel(Request $request, Channel $channel): Response
    {
        $this->abortUnlessBridgeEnabled();

        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            Response::HTTP_NOT_FOUND
        );

        $sourceId = $request->integer('source');
        $stream = null;

        if ($sourceId > 0) {
            $stream = ChannelStream::query()
                ->whereKey($sourceId)
                ->where('channel_id', $channel->id)
                ->where('is_active', true)
                ->first();
        }

        $url = $stream?->stream_url ?: $channel->stream_url;

        $this->abortUnlessAllowedStreamUrl($url);
        $this->logBridgeAttempt($request, $url, $channel, $stream);

        return $this->bridge($url);
    }

    private function bridge(string $url): Response
    {
        if (StreamUrl::isLikelyPlaylistUrl($url)) {
            return $this->bridgePlaylist($url);
        }

        return $this->bridgeStream($url);
    }

    private function bridgePlaylist(string $url): Response
    {
        try {
            $response = Http::connectTimeout(8)
                ->timeout(12)
                ->accept('application/vnd.apple.mpegurl, application/x-mpegURL, text/plain, */*')
                ->withHeaders([
                    'User-Agent' => 'VLC/3.0.20 LibVLC/3.0.20 RifiMediaBrowserBridge/1.0',
                ])
                ->get($url);
        } catch (ConnectionException) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source could not be reached.');
        }

        if (! $response->successful()) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source returned HTTP '.$response->status().'.');
        }

        return response($this->rewritePlaylist((string) $response->body(), $url), Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    private function bridgeStream(string $url): Response
    {
        try {
            $response = Http::withOptions(['stream' => true])
                ->connectTimeout(8)
                ->timeout(0)
                ->withHeaders([
                    'Accept' => '*/*',
                    'Icy-MetaData' => '1',
                    'User-Agent' => 'VLC/3.0.20 LibVLC/3.0.20 RifiMediaBrowserBridge/1.0',
                ])
                ->get($url);
        } catch (ConnectionException) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source could not be reached.');
        }

        if (! $response->successful()) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source returned HTTP '.$response->status().'.');
        }

        $contentType = StreamUrl::contentTypeFor($url, $response->header('Content-Type'));
        $body = $response->toPsrResponse()->getBody();

        return response()->stream(function () use ($body): void {
            while (! $body->eof()) {
                echo $body->read(1024 * 64);

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }
        }, Response::HTTP_OK, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Access-Control-Allow-Origin' => '*',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function rewritePlaylist(string $body, string $baseUrl): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];

        return implode("\n", array_map(function (string $line) use ($baseUrl): string {
            $trimmed = trim($line);

            if ($trimmed === '') {
                return $line;
            }

            if (str_starts_with($trimmed, '#')) {
                return (string) preg_replace_callback(
                    '/URI="([^"]+)"/',
                    fn (array $matches): string => 'URI="'.StreamUrl::signedBridge($this->resolve($matches[1], $baseUrl)).'"',
                    $line
                );
            }

            return StreamUrl::signedBridge($this->resolve($trimmed, $baseUrl)) ?? $line;
        }, $lines));
    }

    private function resolve(string $candidate, string $baseUrl): string
    {
        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $candidate) === 1) {
            return $candidate;
        }

        $base = parse_url($baseUrl);

        if ($base === false || ! isset($base['scheme'], $base['host'])) {
            return $candidate;
        }

        if (str_starts_with($candidate, '//')) {
            return $base['scheme'].':'.$candidate;
        }

        $basePath = $base['path'] ?? '/';
        $directory = (string) preg_replace('#/[^/]*$#', '/', $basePath) ?: '/';
        $resolvedPath = str_starts_with($candidate, '/') ? $candidate : $directory.$candidate;
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        return $base['scheme'].'://'.$base['host'].$port.$resolvedPath;
    }

    private function abortUnlessBridgeEnabled(): void
    {
        abort_unless((bool) config('rifimedia.stream_bridge.enabled'), Response::HTTP_NOT_FOUND);
    }

    private function abortUnlessAllowedStreamUrl(?string $url): void
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid stream URL.');
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            abort(Response::HTTP_BAD_REQUEST, 'Unsupported stream URL scheme.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            abort(Response::HTTP_FORBIDDEN, 'Stream source not allowed.');
        }

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            abort(Response::HTTP_FORBIDDEN, 'Stream source not allowed.');
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : null;

        if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            abort(Response::HTTP_FORBIDDEN, 'Stream source not allowed.');
        }
    }

    private function logBridgeAttempt(Request $request, string $url, ?Channel $channel = null, ?ChannelStream $stream = null): void
    {
        Log::info('stream.bridge', [
            'channel_id' => $channel?->id,
            'channel_stream_id' => $stream?->id,
            'url' => StreamUrl::masked($url),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
        ]);
    }
}

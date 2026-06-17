<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelStream;
use App\Models\IptvItem;
use App\Models\IptvItemSource;
use App\Models\WorldCupMatch;
use App\Services\StreamingPolicy;
use App\Support\StreamUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class StreamBridgeController extends Controller
{
    public function __construct(
        private readonly StreamingPolicy $streamingPolicy,
    ) {}

    public function __invoke(Request $request, string $encodedUrl): Response
    {
        $url = StreamUrl::decodeProxyUrl($encodedUrl);

        $this->abortUnlessAllowedStreamUrl($url);
        $this->logBridgeAttempt($request, $url);

        return $this->bridge($url, $request);
    }

    public function playIptvItem(Request $request, IptvItem $item): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->preflightResponse();
        }

        abort_unless(
            IptvItem::query()->publicLive()->whereKey($item->getKey())->exists(),
            Response::HTTP_NOT_FOUND
        );

        if ($request->integer('match') > 0) {
            $match = WorldCupMatch::query()
                ->with(['selectedIptvItem.playlist', 'iptvItems.playlist'])
                ->findOrFail($request->integer('match'));

            abort_unless($match->isWatchOpen(), Response::HTTP_GONE);
            abort_unless(
                $match->availableWatchItems()->contains(
                    fn (IptvItem $availableItem): bool => $availableItem->is($item)
                ),
                Response::HTTP_NOT_FOUND
            );
        }

        $url = $item->primaryStreamUrl();
        $this->abortUnlessAllowedStreamUrl($url);

        if (app()->isLocal()) {
            Log::debug('live-tv.play', [
                'route' => 'stream.bridge.iptv-item',
                'channel_id' => $item->id,
                'channel_name' => $item->name,
                'stream_type' => $item->extension ?: 'stream',
            ]);
        }

        return $this->bridge($url, $request);
    }

    public function playIptvItemSource(Request $request, IptvItemSource $source): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->preflightResponse();
        }

        $source->load('item');

        abort_unless(
            $source->is_active
            && $source->item
            && IptvItem::query()->publicLive()->whereKey($source->item->getKey())->exists(),
            Response::HTTP_NOT_FOUND
        );

        $this->abortUnlessAllowedStreamUrl($source->url);

        if (app()->isLocal()) {
            Log::debug('stream.bridge.iptv-source', [
                'iptv_item_id' => $source->iptv_item_id,
                'iptv_item_source_id' => $source->id,
                'url' => StreamUrl::masked($source->url),
                'ip_hash' => hash('sha256', (string) $request->ip()),
            ]);
        }

        return $this->bridge($source->url, $request);
    }

    public function playChannel(Request $request, Channel $channel): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->preflightResponse();
        }

        $this->abortUnlessBridgeEnabled();

        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            Response::HTTP_NOT_FOUND
        );

        if ($request->integer('match') > 0) {
            $match = WorldCupMatch::query()->findOrFail($request->integer('match'));

            abort_unless(
                $match->isWatchOpen() && $match->selected_channel_id === $channel->getKey(),
                Response::HTTP_GONE
            );
        }

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
        $this->abortUnlessPolicyAllows($url);
        $this->logBridgeAttempt($request, $url, $channel, $stream);

        return $this->bridge($url, $request);
    }

    private function bridge(string $url, Request $request): Response
    {
        if (StreamUrl::isLikelyPlaylistUrl($url)) {
            return $this->bridgePlaylist($url);
        }

        return $this->bridgeStream($url, $request);
    }

    private function bridgePlaylist(string $url): Response
    {
        set_time_limit(0);
        ignore_user_abort(true);

        try {
            $response = Http::connectTimeout(10)
                ->timeout(30)
                ->retry(1, 250)
                ->accept('application/vnd.apple.mpegurl, application/x-mpegURL, text/plain, */*')
                ->withHeaders([
                    'User-Agent' => 'VLC/3.0.20 LibVLC/3.0.20 RifiMediaBrowserBridge/1.0',
                ])
                ->get($url);
        } catch (ConnectionException) {
            abort(Response::HTTP_BAD_GATEWAY, __('Stream source could not be reached.'));
        }

        if (! $response->successful()) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source returned HTTP '.$response->status().'.');
        }

        return response($this->rewritePlaylist((string) $response->body(), $url), Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            ...$this->streamHeaders(),
        ]);
    }

    private function bridgeStream(string $url, Request $request): Response
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $range = $request->header('Range');
        $headers = [
            'Accept' => '*/*',
            'Icy-MetaData' => '1',
            'User-Agent' => 'VLC/3.0.20 LibVLC/3.0.20 RifiMediaBrowserBridge/1.0',
        ];

        if (is_string($range) && preg_match('/^bytes=\d*-\d*$/', $range) === 1) {
            $headers['Range'] = $range;
        }

        try {
            $response = Http::withOptions(['stream' => true])
                ->connectTimeout(10)
                ->timeout(0)
                ->retry(1, 250)
                ->withOptions(['read_timeout' => 30])
                ->withHeaders($headers)
                ->get($url);
        } catch (ConnectionException) {
            abort(Response::HTTP_BAD_GATEWAY, __('Stream source could not be reached.'));
        }

        if (! $response->successful()) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source returned HTTP '.$response->status().'.');
        }

        $contentType = StreamUrl::contentTypeFor($url, $response->header('Content-Type'));
        $body = $response->toPsrResponse()->getBody();

        $status = $response->status() === Response::HTTP_PARTIAL_CONTENT
            ? Response::HTTP_PARTIAL_CONTENT
            : Response::HTTP_OK;
        $responseHeaders = [
            'Content-Type' => $contentType,
            ...$this->streamHeaders(),
        ];

        foreach (['Content-Range', 'Accept-Ranges', 'Content-Length'] as $header) {
            if ($response->header($header)) {
                $responseHeaders[$header] = $response->header($header);
            }
        }

        return response()->stream(function () use ($body): void {
            try {
                while (! $body->eof() && ! connection_aborted()) {
                    echo $body->read(1024 * 64);

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();
                }
            } finally {
                $body->close();
            }
        }, $status, $responseHeaders);
    }

    private function preflightResponse(): Response
    {
        return response('', Response::HTTP_NO_CONTENT, $this->streamHeaders());
    }

    /**
     * @return array<string, string>
     */
    private function streamHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Range, Origin, Accept, Content-Type',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Accept-Ranges' => 'bytes',
            'X-Accel-Buffering' => 'no',
        ];
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
        abort_unless(
            (bool) config('rifimedia.stream_bridge.enabled')
            && (bool) config('streaming.bridge_enabled'),
            Response::HTTP_NOT_FOUND
        );
    }

    private function abortUnlessAllowedStreamUrl(?string $url): void
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(Response::HTTP_BAD_REQUEST, __('Invalid stream URL.'));
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            abort(Response::HTTP_BAD_REQUEST, __('Unsupported stream URL scheme.'));
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            abort(Response::HTTP_FORBIDDEN, __('Stream source not allowed.'));
        }

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            abort(Response::HTTP_FORBIDDEN, __('Stream source not allowed.'));
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : null;

        if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            abort(Response::HTTP_FORBIDDEN, __('Stream source not allowed.'));
        }
    }

    private function abortUnlessPolicyAllows(string $url): void
    {
        try {
            $this->streamingPolicy->assertStreamUrlAllowed($url);
        } catch (ValidationException) {
            abort(Response::HTTP_FORBIDDEN, __('Stream source is not approved.'));
        }
    }

    private function logBridgeAttempt(Request $request, string $url, ?Channel $channel = null, ?ChannelStream $stream = null): void
    {
        if (! app()->isLocal()) {
            return;
        }

        Log::info('stream.bridge', [
            'channel_id' => $channel?->id,
            'channel_stream_id' => $stream?->id,
            'url' => StreamUrl::masked($url),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelStream;
use App\Support\StreamUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StreamProxyController extends Controller
{
    public function __invoke(Request $request, string $encodedUrl): \Illuminate\Http\RedirectResponse
    {
        $url = $this->decodeUrl($encodedUrl);

        $this->abortUnlessAllowedStreamUrl($url);
        $this->logRedirectAttempt($request, $url);

        return redirect()->away($url);
    }

    public function playChannel(Request $request, Channel $channel): \Illuminate\Http\RedirectResponse
    {
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
        $this->logRedirectAttempt($request, $url, $channel, $stream);

        return redirect()->away($url);
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

    private function logRedirectAttempt(Request $request, string $url, ?Channel $channel = null, ?ChannelStream $stream = null): void
    {
        Log::info('stream.redirect', [
            'channel_id' => $channel?->id,
            'channel_stream_id' => $stream?->id,
            'url' => StreamUrl::masked($url),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
        ]);
    }

    private function decodeUrl(string $encodedUrl): ?string
    {
        $normalized = strtr($encodedUrl, '-_', '+/');

        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);

        return $decoded !== false ? $decoded : null;
    }
}

<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\ChannelStream;
use App\Support\StreamUrl;

class StreamService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function sourcesFor(Channel $channel): array
    {
        $channels = Channel::query()
            ->where('is_active', true)
            ->whereRaw("COALESCE(NULLIF(normalized_name, ''), LOWER(TRIM(name))) = ?", [
                $channel->normalized_name ?: Channel::normalizeName($channel->name),
            ])
            ->with(['streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])
            ->orderBy('id')
            ->get();

        if ($channels->isEmpty()) {
            $channels = collect([$channel->loadMissing(['streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])]);
        }

        $seen = [];
        $sources = [];

        foreach ($channels as $sibling) {
            $sibling->loadMissing(['streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')]);

            if ($sibling->streams->isNotEmpty()) {
                foreach ($sibling->streams as $stream) {
                    $this->appendSource($sources, $seen, $stream, $sibling);
                }

                continue;
            }

            if ($sibling->stream_url) {
                $this->appendLegacySource($sources, $seen, $sibling);
            }
        }

        return array_values($sources);
    }

    public function isPlayable(Channel $channel): bool
    {
        return $channel->is_active
            && $channel->is_live
            && $this->sourcesFor($channel) !== [];
    }

    private function appendSource(array &$sources, array &$seen, ChannelStream $stream, Channel $channel): void
    {
        $hash = strtolower($stream->stream_hash ?: sha1($this->normalizeUrlForHash($stream->stream_url)));

        if (isset($seen[$hash])) {
            return;
        }

        $seen[$hash] = true;
        $serverNumber = count($sources) + 1;

        $playbackUrl = StreamUrl::channelRedirect($channel->id, $stream->id);
        $requiresExternal = $this->requiresExternalPlayer($stream->stream_url);

        $sources[] = [
            'url' => $playbackUrl,
            'external_url' => $playbackUrl,
            'browser_url' => $requiresExternal ? StreamUrl::channelBridge($channel->id, $stream->id) : $playbackUrl,
            'type' => $stream->stream_type ?? $channel->stream_type ?? 'stream',
            'label' => $this->displayLabel($stream->label, $serverNumber),
            'quality' => $stream->quality ?? null,
            'health_status' => $stream->health_status ?? 'unknown',
            'source_id' => $stream->id,
            'requires_external_player' => $requiresExternal,
        ];
    }

    private function appendLegacySource(array &$sources, array &$seen, Channel $channel): void
    {
        $hash = strtolower($channel->stream_hash ?: sha1($this->normalizeUrlForHash($channel->stream_url)));

        if (isset($seen[$hash])) {
            return;
        }

        $seen[$hash] = true;
        $serverNumber = count($sources) + 1;

        $playbackUrl = StreamUrl::channelRedirect($channel->id);
        $requiresExternal = $this->requiresExternalPlayer($channel->stream_url);

        $sources[] = [
            'url' => $playbackUrl,
            'external_url' => $playbackUrl,
            'browser_url' => $requiresExternal ? StreamUrl::channelBridge($channel->id) : $playbackUrl,
            'type' => $channel->stream_type ?? 'stream',
            'label' => 'Server '.$serverNumber,
            'quality' => null,
            'health_status' => 'unknown',
            'requires_external_player' => $requiresExternal,
        ];
    }

    private function normalizeUrlForHash(string $url): string
    {
        return strtolower(trim($url));
    }

    private function displayLabel(?string $label, int $serverNumber): string
    {
        if ($label && preg_match('/^server\s+\d+$/i', trim($label)) !== 1) {
            return $label;
        }

        return 'Server '.$serverNumber;
    }

    private function requiresExternalPlayer(?string $url): bool
    {
        return strtolower((string) parse_url((string) $url, PHP_URL_SCHEME)) === 'http';
    }
}

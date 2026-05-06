<?php

namespace App\Services;

use App\Models\Channel;

class StreamService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function sourcesFor(Channel $channel): array
    {
        $channel->loadMissing(['streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')]);

        return $channel->active_stream_sources
            ->map(fn (array $source) => [
                'url' => $source['url'],
                'type' => $source['type'] ?? 'hls',
                'label' => $source['label'] ?? 'Primary',
                'quality' => $source['quality'] ?? null,
                'health_status' => $source['health_status'] ?? 'unknown',
            ])
            ->values()
            ->all();
    }

    public function isPlayable(Channel $channel): bool
    {
        return $channel->is_active
            && $channel->is_live
            && $this->sourcesFor($channel) !== [];
    }
}

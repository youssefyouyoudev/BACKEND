<?php

namespace App\Http\Resources\Mobile;

use App\Models\Channel;
use App\Services\StreamService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Channel */
class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->relationLoaded('category') ? $this->getRelation('category') : null;
        $includePlayer = $request->boolean('include_player')
            || $request->routeIs('api.mobile.channels.show');
        $sources = $includePlayer ? app(StreamService::class)->sourcesFor($this->resource) : [];
        $quality = $this->qualityLabel();

        return [
            'id' => $this->id,
            'name' => $this->clean_display_name,
            'original_name' => $this->name,
            'slug' => $this->slug,
            'tvg_id' => $this->tvg_id,
            'category' => [
                'id' => $category?->id,
                'name' => $category?->name ?? $this->group_title ?? 'General',
                'slug' => $category?->slug,
            ],
            'status' => [
                'is_active' => (bool) $this->is_active,
                'is_live' => (bool) $this->is_live,
                'is_featured' => (bool) $this->is_featured,
                'health' => $this->healthStatus(),
            ],
            'quality' => [
                'label' => $quality,
                'sd' => $quality === 'SD',
                'hd' => $quality === 'HD',
                'fhd' => $quality === 'FHD',
            ],
            'logo_url' => $this->logoUrl(),
            'image_url' => $this->logoUrl(),
            'thumbnail_url' => $this->logoUrl(),
            'program' => $this->when($this->relationLoaded('currentProgram') && $this->currentProgram, fn () => [
                'title' => $this->currentProgram->title,
                'start_time' => $this->currentProgram->start_time?->toIso8601String(),
                'end_time' => $this->currentProgram->end_time?->toIso8601String(),
            ]),
            'player_url' => $this->when($includePlayer, $sources[0]['url'] ?? null),
            'stream_url' => $this->when($includePlayer, $sources[0]['url'] ?? null),
            'sources' => $this->when($includePlayer, $sources),
            'links' => [
                'web_url' => route('channels.show', $this->resource),
                'api_url' => route('api.mobile.channels.show', $this->id),
            ],
        ];
    }

    private function logoUrl(): string
    {
        return $this->logo ?: asset('brand/rifi-logo.png');
    }

    private function qualityLabel(): string
    {
        $quality = strtoupper((string) ($this->metadata['quality'] ?? $this->quality_label));

        if (in_array($quality, ['SD', 'HD', 'FHD'], true)) {
            return $quality;
        }

        if (in_array($quality, ['4K', 'UHD'], true)) {
            return 'FHD';
        }

        if ($this->relationLoaded('streams')) {
            $streamQuality = strtoupper((string) ($this->streams->first()?->quality ?? ''));

            if (str_contains($streamQuality, '720') || str_contains($streamQuality, 'HD')) {
                return 'HD';
            }

            if (str_contains($streamQuality, '1080') || str_contains($streamQuality, 'FHD')) {
                return 'FHD';
            }

            if (str_contains($streamQuality, '480') || str_contains($streamQuality, 'SD')) {
                return 'SD';
            }
        }

        return 'HD';
    }

    private function healthStatus(): string
    {
        if (! $this->relationLoaded('streams') || $this->streams->isEmpty()) {
            return $this->stream_url ? 'unknown' : 'offline';
        }

        $statuses = $this->streams->pluck('health_status')->filter()->map(fn (string $status) => strtolower($status));

        if ($statuses->contains(fn (string $status) => in_array($status, ['ok', 'healthy', 'online'], true))) {
            return 'online';
        }

        if ($statuses->contains(fn (string $status) => in_array($status, ['failed', 'offline', 'error'], true))) {
            return 'degraded';
        }

        return 'unknown';
    }
}

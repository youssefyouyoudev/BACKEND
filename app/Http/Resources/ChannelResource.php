<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Channel */
class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'group_title' => $this->group_title,
            'stream_url' => $this->stream_url,
            'stream_type' => $this->stream_type,
            'tvg_id' => $this->tvg_id,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'metadata' => $this->metadata,
            'playlist' => [
                'id' => $this->playlist_id,
                'name' => $this->whenLoaded('playlist', fn () => $this->playlist->name),
            ],
            'is_favorite' => $user
                ? ($this->relationLoaded('favoredByUsers')
                    ? $this->favoredByUsers->contains($user->id)
                    : $user->favorites()->where('channel_id', $this->id)->exists())
                : false,
            'last_watched_at' => $this->whenLoaded('watchHistories', function () use ($user) {
                $history = $this->watchHistories
                    ->when($user, fn ($collection) => $collection->where('user_id', $user->id))
                    ->sortByDesc('watched_at')
                    ->first();

                return $history?->watched_at?->toIso8601String();
            }),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WatchHistory */
class WatchHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'watched_at' => $this->watched_at?->toIso8601String(),
            'duration' => $this->duration,
            'channel' => $this->whenLoaded('channel', fn () => new ChannelResource($this->channel)),
        ];
    }
}

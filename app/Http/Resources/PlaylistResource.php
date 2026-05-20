<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Playlist */
class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'source_type'       => $this->source_type,
            'source_url'        => $this->source_url,
            'file_path'         => $this->file_path,
            'original_filename' => $this->original_filename,
            'status'            => $this->status,
            'last_synced_at'    => $this->last_synced_at?->toIso8601String(),
            'is_public'         => $this->is_public,
            'approved_at'       => $this->approved_at?->toIso8601String(),
            'import_summary'    => $this->import_summary,
            'channels_count'    => $this->whenCounted('channels'),
            // Groups come from import_summary (always available, no extra query needed)
            'categories'        => $this->import_summary['groups'] ?? [],
            'owner'             => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'channels'          => ChannelResource::collection($this->whenLoaded('channels')),
        ];
    }
}

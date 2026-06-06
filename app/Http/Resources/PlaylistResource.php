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
            'input_type'        => $this->input_type,
            'source_type'       => $this->source_type,
            'm3u_url'           => $this->masked_m3u_url,
            'source_url'        => $this->masked_m3u_url,
            'file_path'         => $this->file_path,
            'original_filename' => $this->original_filename,
            'has_active_code'   => filled($this->active_code),
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

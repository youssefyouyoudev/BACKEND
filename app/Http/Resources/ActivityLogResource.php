<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ActivityLog */
class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
        ];
    }
}

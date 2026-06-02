<?php

namespace App\Http\Resources\Mobile;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'icon' => $this->icon,
            'status' => [
                'is_active' => (bool) $this->is_active,
            ],
            'channels_count' => (int) ($this->channels_count ?? 0),
            'links' => [
                'channels_url' => route('api.mobile.categories.channels', $this->slug),
            ],
        ];
    }
}

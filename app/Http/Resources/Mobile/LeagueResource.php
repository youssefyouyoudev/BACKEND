<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'slug' => $this->resource['slug'],
            'country' => $this->resource['country'],
            'status' => [
                'value' => $this->resource['status'],
                'is_active' => $this->resource['status'] === 'active',
            ],
            'image_url' => $this->resource['image_url'],
            'links' => [
                'web_url' => route('leagues.show', $this->resource['slug']),
            ],
        ];
    }
}

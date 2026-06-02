<?php

namespace App\Http\Resources\Mobile;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->when($request->routeIs('api.mobile.news.show'), $this->body),
            'reading_time_minutes' => max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 220)),
            'status' => [
                'value' => $this->status,
                'is_published' => $this->status === 'published',
            ],
            'category' => $this->when($this->category, fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'author' => $this->when($this->author, fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'image_url' => $this->imageUrl(),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'web_url' => route('news.show', $this->slug),
                'api_url' => route('api.mobile.news.show', $this->slug),
            ],
        ];
    }

    private function imageUrl(): string
    {
        $image = Str::of((string) $this->featured_image)->trim()->toString();

        if ($image !== '') {
            return $image;
        }

        return asset('brand/rifi-logo.png');
    }
}

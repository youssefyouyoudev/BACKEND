<?php

namespace App\Services;

use App\Models\IptvItem;
use App\Support\StreamUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicLiveTvService
{
    public function query(?string $category = null, ?string $search = null): Builder
    {
        $category = trim((string) $category);
        $search = trim((string) $search);

        return IptvItem::query()
            ->publicLive()
            ->when($category !== '' && $category !== '__ALL__', function (Builder $query) use ($category): void {
                $query->where(function (Builder $categoryQuery) use ($category): void {
                    $categoryQuery
                        ->where('group_title', $category)
                        ->orWhereHas('category', fn (Builder $relation) => $relation->where('name', $category));
                });
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('group_title', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * @return Collection<int, IptvItem>
     */
    public function channels(Builder $query): Collection
    {
        return $query
            ->with('category:id,name')
            ->orderBy('name')
            ->limit((int) config('streaming.public_catalog_limit', 500))
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(IptvItem $item): array
    {
        $category = $item->category?->name ?: $item->group_title ?: 'General';

        return [
            'id' => $item->id,
            'name' => $item->name,
            'original_name' => $item->name,
            'logo' => $this->safeImageUrl($item->logo),
            'category' => $category,
            'group_title' => $category,
            'quality' => $item->qualityLabel(),
            'quality_label' => $item->qualityLabel(),
            'stream_type' => $item->extension ?: 'stream',
            'public_play_url' => StreamUrl::iptvItemBridge($item->id),
            'watch_url' => route('watch.item', $item),
            'playback_status' => [
                'playable' => true,
                'code' => 'ready',
                'message' => 'Protected playback is ready.',
            ],
        ];
    }

    private function safeImageUrl(?string $url): string
    {
        if (! is_string($url) || trim($url) === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return asset('brand/rifi-logo.png');
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
            ? $url
            : asset('brand/rifi-logo.png');
    }
}

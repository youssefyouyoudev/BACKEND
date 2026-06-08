<?php

namespace App\Services;

use App\Models\IptvItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CuratedSportsCatalogService
{
    /**
     * @return Collection<int, IptvItem>
     */
    public function items(Builder $query, ?string $search = null): Collection
    {
        $groups = $query
            ->with(['category', 'playlist:id,name'])
            ->limit((int) config('sports_channels.query_limit', 1500))
            ->get()
            ->groupBy(fn (IptvItem $item): ?string => $this->featuredLabel($item->name))
            ->filter(fn (Collection $variants, ?string $label): bool => $label !== null && $variants->isNotEmpty());

        $search = mb_strtolower(trim((string) $search));
        $ordered = collect(config('sports_channels.featured', []))
            ->pluck('label')
            ->filter(fn (string $label): bool => $search === '' || str_contains(mb_strtolower($label), $search))
            ->take((int) config('sports_channels.max_channels', 30));

        return $ordered
            ->flatMap(function (string $label) use ($groups): Collection {
                return $groups->get($label, collect())
                    ->sortBy(fn (IptvItem $item): int => $this->qualityPriority($item->name))
                    ->values();
            })
            ->values();
    }

    public function selectedChannelCount(Builder $query, ?string $search = null): int
    {
        return $this->items($query, $search)
            ->map(fn (IptvItem $item): ?string => $this->featuredLabel($item->name))
            ->filter()
            ->unique()
            ->count();
    }

    public function isSelected(IptvItem $item): bool
    {
        return $this->featuredLabel($item->name) !== null;
    }

    public function featuredLabel(?string $name): ?string
    {
        $normalized = $this->normalizeName($name);

        foreach (config('sports_channels.featured', []) as $channel) {
            if (preg_match($channel['pattern'], $normalized) === 1) {
                return $channel['label'];
            }
        }

        return null;
    }

    private function normalizeName(?string $name): string
    {
        $name = mb_strtolower((string) $name);
        $name = preg_replace('/^\s*(?:[✦●]+\s*)?(?:\|[^|]+\||\[[^\]]+\])\s*/u', '', $name) ?? $name;
        $name = str_replace(['beinsports', 'bein sport ', 'be in sports'], ['bein sports', 'bein sports ', 'bein sports'], $name);
        $name = preg_replace('/(?<=[a-z])(?=\d)|(?<=\d)(?=[a-z])/u', ' ', $name) ?? $name;
        $name = preg_replace('/\b(?:uhd|4k|8k|fhd|full\s*hd|hd\d?|sd|1080p|720p|480p|hevc|h\.?265)\b/ui', ' ', $name) ?? $name;
        $name = preg_replace('/\b(?:match time|only events?|live|sat|tnt|olympics?)\b/ui', ' ', $name) ?? $name;
        $name = preg_replace('/[^a-z0-9]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function qualityPriority(string $name): int
    {
        return match (true) {
            preg_match('/\b(?:4K|UHD|2160P)\b/i', $name) === 1 => 0,
            preg_match('/\b(?:FHD|FULL\s*HD|1080P)\b/i', $name) === 1 => 1,
            preg_match('/\b(?:HD|720P)\b/i', $name) === 1 => 2,
            preg_match('/\bSD\b/i', $name) === 1 => 4,
            default => 3,
        };
    }
}

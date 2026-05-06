<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Channel;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TvCatalogService
{
    public function categories(): Collection
    {
        return Cache::remember('tv:categories', now()->addMinutes(10), fn () => Category::query()
            ->where('is_active', true)
            ->withCount(['channels' => fn (Builder $query) => $this->publicChannelConstraints($query)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get());
    }

    public function featuredChannels(int $limit = 12): Collection
    {
        return Cache::remember("tv:featured:{$limit}", now()->addMinutes(5), fn () => $this->publicChannels()
            ->with(['category', 'playlist', 'currentProgram'])
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get());
    }

    public function channels(?string $category = null, ?string $search = null, int $limit = 60): Collection
    {
        return $this->publicChannels()
            ->with(['category', 'playlist', 'currentProgram', 'streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])
            ->when($category, fn (Builder $query) => $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category)))
            ->when($search, fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function epg(Carbon $start, Carbon $end, ?string $category = null): Collection
    {
        $channels = $this->channels($category, null, 80);
        $programs = Program::query()
            ->whereIn('channel_id', $channels->pluck('id'))
            ->window($start, $end)
            ->orderBy('start_time')
            ->get()
            ->groupBy('channel_id');

        return $channels->map(fn (Channel $channel) => [
            'channel' => $channel,
            'programs' => $programs->get($channel->id, collect()),
        ]);
    }

    public function publicChannels(): Builder
    {
        return Channel::query()
            ->where('is_active', true)
            ->where('is_live', true)
            ->whereHas('playlist', fn (Builder $query) => $query->where('is_public', true)->whereNotNull('approved_at'));
    }

    private function publicChannelConstraints(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_live', true)
            ->whereHas('playlist', fn (Builder $playlistQuery) => $playlistQuery
                ->where('is_public', true)
                ->whereNotNull('approved_at'));
    }
}

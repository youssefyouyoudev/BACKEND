<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class LiveTvController extends Controller
{
    public function __invoke(): View
    {
        // Category list with per-category channel counts (two queries, no N+1)
        $publicPlaylistScope = fn (Builder $q) => $q
            ->where('is_public', true)
            ->whereNotNull('approved_at');

        $baseQuery = fn () => Channel::query()
            ->where('is_active', true)
            ->whereHas('playlist', $publicPlaylistScope);

        $totalCount = Cache::remember('public-live:total-count', now()->addMinutes(10), fn () => $baseQuery()->count());

        $categoryCounts = Cache::remember('public-live:category-counts', now()->addMinutes(10), fn () => $baseQuery()
            ->whereNotNull('group_title')
            ->selectRaw('group_title, COUNT(*) as cnt')
            ->groupBy('group_title')
            ->orderBy('group_title')
            ->pluck('cnt', 'group_title'));

        // Serve the first page of ALL channels server-side so the UI has
        // content immediately without waiting for an AJAX round-trip.
        $initialChannels = Cache::remember('public-live:initial-channels', now()->addMinutes(5), fn () => $baseQuery()
            ->with(['streams' => fn ($q) => $q->where('is_active', true)->orderBy('priority')])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(60)
            ->get()
            ->map(fn (Channel $ch) => $this->serializeLiveChannel($ch)));

        return view('public.live', compact('totalCount', 'categoryCounts', 'initialChannels'));
    }

    private function serializeLiveChannel(Channel $channel): array
    {
        $viewerCount = 1200 + (($channel->id * 137) % 184000);

        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'thumbnail' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'group_title' => $channel->group_title ?: 'General',
            'description' => ($channel->group_title ?: 'Live TV').' stream from '.($channel->playlist?->name ?? 'an approved public playlist').'.',
            'viewers' => $viewerCount,
            'viewers_label' => $viewerCount >= 1000 ? round($viewerCount / 1000, 1).'K' : (string) $viewerCount,
            'sources' => $channel->active_stream_sources->toArray(),
        ];
    }
}

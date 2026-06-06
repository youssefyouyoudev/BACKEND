<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Support\StreamUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class LiveTvController extends Controller
{
    public function __invoke(): View
    {
        $publicPlaylistScope = fn (Builder $q) => $q
            ->where('is_public', true)
            ->whereNotNull('approved_at');

        $baseQuery = fn () => IptvItem::query()
            ->visible()
            ->where('type', IptvItem::TYPE_LIVE)
            ->where('is_adult', false)
            ->whereHas('playlist', $publicPlaylistScope);

        $totalCount = Cache::remember('public-live:iptv-total-count', now()->addMinutes(10), fn () => $baseQuery()->count());

        $categoryCounts = Cache::remember('public-live:iptv-category-counts', now()->addMinutes(10), fn () => IptvCategory::query()
            ->where('type', IptvCategory::TYPE_LIVE)
            ->whereHas('playlist', $publicPlaylistScope)
            ->withCount(['items' => fn (Builder $query) => $query
                ->visible()
                ->where('type', IptvItem::TYPE_LIVE)
                ->where('is_adult', false)])
            ->whereHas('items', fn (Builder $query) => $query
                ->visible()
                ->where('type', IptvItem::TYPE_LIVE)
                ->where('is_adult', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('items_count', 'name'));

        $initialChannels = Cache::remember('public-live:iptv-initial-items', now()->addMinutes(5), fn () => $baseQuery()
            ->with(['category', 'playlist:id,name'])
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->limit(60)
            ->get()
            ->map(fn (IptvItem $item) => $this->serializeLiveItem($item)));

        return view('public.live', compact('totalCount', 'categoryCounts', 'initialChannels'));
    }

    private function serializeLiveItem(IptvItem $item): array
    {
        $viewerCount = 1200 + (($item->id * 137) % 184000);
        $category = $item->category?->name ?: $item->group_title ?: 'General';

        return [
            'id' => $item->id,
            'name' => $item->name,
            'original_name' => $item->name,
            'display_tags' => [],
            'quality_label' => $this->qualityLabel($item),
            'logo' => $item->logo ?: asset('brand/rifi-logo.png'),
            'thumbnail' => $item->logo ?: asset('brand/rifi-logo.png'),
            'group_title' => $category,
            'language_label' => 'Global',
            'status_label' => 'On air',
            'description' => $item->description ?: "{$category} stream from ".($item->playlist?->name ?? 'an approved public playlist').'.',
            'viewers' => $viewerCount,
            'viewers_label' => $viewerCount >= 1000 ? round($viewerCount / 1000, 1).'K' : (string) $viewerCount,
            'watch_url' => route('watch.item', $item),
            'sources' => $this->sourcesFor($item),
        ];
    }

    private function qualityLabel(IptvItem $item): string
    {
        if (preg_match('/\b(4K|UHD|FHD|HD|SD)\b/i', $item->name, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return 'HD';
    }

    private function sourcesFor(IptvItem $item): array
    {
        if (! $item->stream_url) {
            return [];
        }

        $requiresExternalPlayer = strtolower((string) parse_url($item->stream_url, PHP_URL_SCHEME)) === 'http';
        $playbackUrl = StreamUrl::signedRedirect($item->stream_url);

        return [[
            'url' => $playbackUrl,
            'external_url' => $playbackUrl,
            'browser_url' => $requiresExternalPlayer ? StreamUrl::signedBridge($item->stream_url) : $playbackUrl,
            'type' => $item->extension ?: 'stream',
            'label' => 'Server 1',
            'quality' => $this->qualityLabel($item),
            'health_status' => 'unknown',
            'requires_external_player' => $requiresExternalPlayer,
        ]];
    }
}

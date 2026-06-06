<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Support\StreamUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public (no-auth) API for the Live TV split-screen UI.
 *
 * Live IPTV items are fetched from playlists that are both public AND approved,
 * so no sensitive or unapproved data is ever exposed.
 */
class PublicTvController extends Controller
{
    private function publicLiveItemBase(): Builder
    {
        return IptvItem::query()
            ->visible()
            ->where('type', IptvItem::TYPE_LIVE)
            ->where('is_adult', false)
            ->whereHas('playlist', fn (Builder $q) => $q
                ->where('is_public', true)
                ->whereNotNull('approved_at'));
    }

    /** GET /api/tv/channels */
    public function channels(Request $request): JsonResponse
    {
        $category = $request->string('category')->toString();
        $search = $request->string('search')->toString();
        $perPage = min(100, max(20, $request->integer('per_page', 60)));

        $cacheKey = 'api-tv:channels:'.md5(json_encode([
            'category' => $category,
            'search' => $search,
            'per_page' => $perPage,
            'page' => $request->integer('page', 1),
        ]));

        $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($category, $search, $perPage): array {
            $channels = $this->publicLiveItemBase()
                ->with(['category', 'playlist:id,name'])
                ->when($category !== '' && $category !== '__ALL__',
                    fn (Builder $q) => $q->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', $category)))
                ->when($search !== '',
                    fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->orderByDesc('updated_at')
                ->orderBy('name')
                ->paginate($perPage);

            return [
                'data' => $channels->getCollection()
                    ->map(fn (IptvItem $item) => $this->serializeItem($item))
                    ->values(),
                'meta' => [
                    'current_page' => $channels->currentPage(),
                    'last_page' => $channels->lastPage(),
                    'total' => $channels->total(),
                ],
            ];
        });

        return response()->json($payload);
    }

    /** GET /api/tv/channels/{channel} */
    public function show(IptvItem $item): JsonResponse
    {
        abort_unless(
            $item->is_active
            && $item->type === IptvItem::TYPE_LIVE
            && ! $item->is_adult
            && $item->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            404
        );

        $payload = Cache::remember("api-tv:iptv-item:{$item->id}", now()->addMinutes(3), function () use ($item): array {
            $item->load(['category', 'playlist:id,name']);

            return $this->serializeItem($item);
        });

        return response()->json(['data' => $payload]);
    }

    /** GET /api/tv/categories */
    public function categories(): JsonResponse
    {
        $payload = Cache::remember('api-tv:categories', now()->addMinutes(10), function (): array {
            $total = $this->publicLiveItemBase()->count();

            $cats = IptvCategory::query()
                ->where('type', IptvCategory::TYPE_LIVE)
                ->whereHas('playlist', fn (Builder $query) => $query
                    ->where('is_public', true)
                    ->whereNotNull('approved_at'))
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
                ->pluck('items_count', 'name');

            return [
                'total' => $total,
                'categories' => $cats,
            ];
        });

        return response()->json($payload);
    }

    private function serializeItem(IptvItem $item): array
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

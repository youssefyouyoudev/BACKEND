<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\WatchHistory;
use App\Services\IptvChannelNormalizer;
use App\Support\StreamUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    public function __construct(
        private readonly IptvChannelNormalizer $normalizer,
    ) {}

    public function index(Request $request): View
    {
        return view('watch.index', [
            'continueWatching' => $this->continueWatching($request),
            'liveCategories' => $this->categories('live'),
            'movieCategories' => $this->categories('movie'),
            'seriesCategories' => $this->categories('series'),
        ]);
    }

    public function live(): View
    {
        return $this->type('live');
    }

    public function movies(): View
    {
        return $this->type('movie');
    }

    public function series(): View
    {
        return $this->type('series');
    }

    public function category(Request $request, IptvCategory $category): View
    {
        abort_if($this->isLockedAdultCategory($request, $category), 403);

        $items = $category->items()
            ->visible()
            ->published()
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->latest('updated_at')
            ->paginate(48)
            ->withQueryString();

        return view('watch.category', [
            'category' => $category,
            'items' => $items,
        ]);
    }

    public function item(Request $request, IptvItem $item): View
    {
        abort_unless($item->is_active && $item->is_public, 404);
        abort_if($item->is_adult && ! $this->adultUnlocked($request), 403);
        abort_unless(filled($item->stream_url), 404);

        $normalizedName = $item->normalized_name ?: $this->normalizer->normalize($item->name);
        $siblings = IptvItem::query()
            ->visible()
            ->published()
            ->where('type', $item->type)
            ->when($item->category_id, fn ($query) => $query->where('category_id', $item->category_id))
            ->orderBy('name')
            ->limit(80)
            ->get();

        $variants = IptvItem::query()
            ->publicLive()
            ->where(function ($query) use ($item, $normalizedName): void {
                $query->where('normalized_name', $normalizedName)
                    ->orWhereKey($item->id);
            })
            ->with(['sources' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])
            ->get();

        $playerSources = $variants
            ->flatMap(function (IptvItem $variant) {
                if ($variant->sources->isNotEmpty()) {
                    return $variant->sources->map(fn ($source): array => [
                        'id' => 'source-'.$source->id,
                        'url' => StreamUrl::iptvItemSourceBridge($source->id),
                        'type' => $source->type,
                        'label' => $source->label,
                        'quality' => $source->quality_label,
                    ]);
                }

                return [[
                    'id' => 'item-'.$variant->id,
                    'url' => StreamUrl::iptvItemBridge($variant->id),
                    'type' => $variant->stream_type ?: $variant->extension ?: 'auto',
                    'label' => $variant->qualityLabel(),
                    'quality' => $variant->qualityLabel(),
                ]];
            })
            ->values();

        return view('watch.item', [
            'item' => $item->load('category'),
            'siblings' => $siblings,
            'browserUrl' => StreamUrl::iptvItemBridge($item->id),
            'playerSources' => $playerSources,
        ]);
    }

    public function search(Request $request): View
    {
        $items = IptvItem::query()
            ->visible()
            ->published()
            ->with('category')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->where(function ($query) use ($request): void {
                if (! $this->adultUnlocked($request)) {
                    $query->where('is_adult', false);
                }
            })
            ->latest('updated_at')
            ->paginate(48)
            ->withQueryString();

        return view('watch.category', [
            'category' => null,
            'items' => $items,
        ]);
    }

    public function favorite(Request $request, IptvItem $item): RedirectResponse
    {
        abort_unless($request->user(), 401);

        $favorite = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('iptv_item_id', $item->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
        } else {
            Favorite::query()->create([
                'user_id' => $request->user()->id,
                'iptv_item_id' => $item->id,
            ]);
        }

        return back()->with('status', $favorite ? 'Removed from favorites.' : 'Added to favorites.');
    }

    public function history(Request $request, IptvItem $item): RedirectResponse
    {
        WatchHistory::query()->updateOrCreate(
            [
                'user_id' => $request->user()?->id,
                'session_id' => $request->user() ? null : $request->session()->getId(),
                'iptv_item_id' => $item->id,
            ],
            [
                'watched_at' => now(),
                'progress_seconds' => max(0, $request->integer('progress_seconds')),
            ]
        );

        return back();
    }

    private function type(string $type): View
    {
        return view('watch.index', [
            'continueWatching' => collect(),
            'liveCategories' => $type === 'live' ? $this->categories('live', 30) : collect(),
            'movieCategories' => $type === 'movie' ? $this->categories('movie', 30) : collect(),
            'seriesCategories' => $type === 'series' ? $this->categories('series', 30) : collect(),
        ]);
    }

    private function categories(string $type, int $limit = 12)
    {
        return IptvCategory::query()
            ->where('type', $type)
            ->whereHas('items', fn ($query) => $query->visible()->published())
            ->withCount(['items' => fn ($query) => $query->visible()->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    private function continueWatching(Request $request)
    {
        if (! $request->user()) {
            return collect();
        }

        return WatchHistory::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('iptv_item_id')
            ->with('iptvItem.category')
            ->latest('watched_at')
            ->limit(12)
            ->get()
            ->pluck('iptvItem')
            ->filter(fn (?IptvItem $item) => $item?->is_active && $item->is_public);
    }

    private function isLockedAdultCategory(Request $request, IptvCategory $category): bool
    {
        return IptvItem::isAdultName($category->name) && ! $this->adultUnlocked($request);
    }

    private function adultUnlocked(Request $request): bool
    {
        return $request->session()->get('parental_unlocked') === true;
    }
}

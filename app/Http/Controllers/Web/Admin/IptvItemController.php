<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateIptvItemVisibilityRequest;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class IptvItemController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $items = $this->filteredItems($request)
            ->paginate(30)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'rows' => view('admin.iptv-items.partials.rows', compact('items'))->render(),
                'pagination' => $items->links()->render(),
                'summary' => $this->summary($items->total()),
            ]);
        }

        return view('admin.iptv-items.index', [
            'items' => $items,
            'playlists' => Playlist::query()
                ->whereHas('iptvItems')
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => IptvCategory::query()
                ->whereHas('items')
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'summary' => $this->summary($items->total()),
        ]);
    }

    public function updateVisibility(
        UpdateIptvItemVisibilityRequest $request,
        IptvItem $item
    ): JsonResponse {
        $item->update([
            'is_public' => $request->boolean('is_public'),
        ]);

        $this->clearPublicCatalogCaches();

        return response()->json([
            'message' => $item->is_public
                ? "{$item->name} is now visible publicly."
                : "{$item->name} is now hidden from the public website.",
            'item' => [
                'id' => $item->id,
                'is_public' => $item->is_public,
            ],
        ]);
    }

    public function updateAllVisibility(UpdateIptvItemVisibilityRequest $request): JsonResponse
    {
        $isPublic = $request->boolean('is_public');
        $updated = IptvItem::query()
            ->where('is_public', ! $isPublic)
            ->update(['is_public' => $isPublic]);

        $this->clearPublicCatalogCaches();

        return response()->json([
            'message' => $isPublic
                ? number_format($updated).' IPTV items were made public.'
                : number_format($updated).' IPTV items were hidden from the public website.',
            'updated' => $updated,
            'is_public' => $isPublic,
        ]);
    }

    private function filteredItems(Request $request): Builder
    {
        $search = $request->string('q')->trim()->toString();
        $type = $request->string('type')->toString();
        $visibility = $request->string('visibility')->toString();

        return IptvItem::query()
            ->with([
                'playlist:id,name',
                'category:id,name,type',
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('tvg_id', 'like', "%{$search}%")
                    ->orWhere('group_title', 'like', "%{$search}%");
            }))
            ->when(in_array($type, [IptvItem::TYPE_LIVE, IptvItem::TYPE_MOVIE, IptvItem::TYPE_SERIES], true),
                fn (Builder $query) => $query->where('type', $type))
            ->when($visibility === 'public', fn (Builder $query) => $query->where('is_public', true))
            ->when($visibility === 'hidden', fn (Builder $query) => $query->where('is_public', false))
            ->when($request->integer('playlist_id') > 0,
                fn (Builder $query) => $query->where('playlist_id', $request->integer('playlist_id')))
            ->when($request->integer('category_id') > 0,
                fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->orderByDesc('is_public')
            ->orderBy('name');
    }

    /**
     * @return array<string, int>
     */
    private function summary(int $filtered): array
    {
        return [
            'filtered' => $filtered,
            'total' => IptvItem::query()->count(),
            'public' => IptvItem::query()->where('is_public', true)->count(),
            'hidden' => IptvItem::query()->where('is_public', false)->count(),
        ];
    }

    private function clearPublicCatalogCaches(): void
    {
        foreach ([
            'public-live:iptv-total-count',
            'public-live:iptv-category-counts',
            'public-live:iptv-initial-items',
            'api-tv:categories',
            'api-tv:curated-sports-categories-v1',
            'api-tv:live-categories-v2',
        ] as $key) {
            Cache::forget($key);
        }
    }
}

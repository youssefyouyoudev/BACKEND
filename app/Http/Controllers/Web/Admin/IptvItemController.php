<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreIptvItemSourceRequest;
use App\Http\Requests\Web\Admin\UpdateIptvItemRequest;
use App\Http\Requests\Web\Admin\UpdateIptvItemSourceRequest;
use App\Http\Requests\Web\Admin\UpdateIptvItemVisibilityRequest;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\IptvItemSource;
use App\Models\Playlist;
use App\Services\IptvChannelNormalizer;
use App\Services\StreamingPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class IptvItemController extends Controller
{
    public function __construct(
        private readonly IptvChannelNormalizer $normalizer,
        private readonly StreamingPolicy $streamingPolicy,
    ) {}

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

    public function edit(IptvItem $item): View
    {
        return view('admin.iptv-items.edit', [
            'item' => $item->load(['playlist:id,name', 'category:id,name', 'sources']),
            'categories' => IptvCategory::query()
                ->where('playlist_id', $item->playlist_id)
                ->where('type', $item->type)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateIptvItemRequest $request, IptvItem $item): RedirectResponse
    {
        $validated = $request->validated();
        $validated['normalized_name'] = $this->normalizer->normalize($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_public'] = $request->boolean('is_public');
        $validated['is_featured'] = $request->boolean('is_featured');

        $item->update($validated);
        $this->clearPublicCatalogCaches();

        return back()->with('status', __('Channel details updated.'));
    }

    public function storeSource(StoreIptvItemSourceRequest $request, IptvItem $item): RedirectResponse
    {
        $item->sources()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->clearPublicCatalogCaches();

        return back()->with('status', __('Backup source added.'));
    }

    public function destroySource(IptvItem $item, IptvItemSource $source): RedirectResponse
    {
        abort_unless($source->iptv_item_id === $item->id, 404);
        $source->delete();
        $this->clearPublicCatalogCaches();

        return back()->with('status', __('Source removed.'));
    }

    public function updateSource(
        UpdateIptvItemSourceRequest $request,
        IptvItem $item,
        IptvItemSource $source
    ): RedirectResponse {
        abort_unless($source->iptv_item_id === $item->id, 404);

        $source->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->clearPublicCatalogCaches();

        return back()->with('status', __('Source priority updated.'));
    }

    public function testSource(IptvItem $item, IptvItemSource $source): RedirectResponse
    {
        abort_unless($source->iptv_item_id === $item->id, 404);

        $started = microtime(true);
        $status = 'offline';
        $responseCode = null;
        $error = null;

        try {
            $this->streamingPolicy->assertStreamUrlAllowed($source->url);
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->withHeaders(['Range' => 'bytes=0-2048', 'Accept' => '*/*'])
                ->get($source->url);
            $responseCode = $response->status();
            $status = $response->successful() || in_array($responseCode, [206, 301, 302, 403, 405], true)
                ? 'online'
                : 'offline';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }

        $source->update([
            'health_status' => $status,
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'response_code' => $responseCode,
            'last_error' => $status === 'online' ? null : $error,
            'last_checked_at' => now(),
            'last_success_at' => $status === 'online' ? now() : $source->last_success_at,
            'success_count' => $status === 'online' ? $source->success_count + 1 : $source->success_count,
            'failure_count' => $status === 'online' ? $source->failure_count : $source->failure_count + 1,
        ]);

        return back()->with('status', $status === 'online'
            ? __('Source is responding.')
            : __('This source is not responding.'));
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

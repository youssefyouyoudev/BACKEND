<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    public function categories(Request $request): JsonResponse
    {
        $categories = IptvCategory::query()
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->whereHas('items', fn ($query) => $query->visible()->published())
            ->withCount(['items' => fn ($query) => $query->visible()->published()])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->paginate(min(100, max(1, $request->integer('per_page', 50))));

        return response()->json($categories);
    }

    public function items(Request $request): JsonResponse
    {
        $items = $this->itemQuery($request)
            ->paginate(min(100, max(1, $request->integer('per_page', 48))));

        return response()->json($items);
    }

    public function show(IptvItem $item): JsonResponse
    {
        abort_unless($item->is_active && $item->is_public, 404);
        abort_if($item->is_adult, 403);

        return response()->json($item->load('category'));
    }

    public function search(Request $request): JsonResponse
    {
        $items = $this->itemQuery($request)
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->paginate(min(100, max(1, $request->integer('per_page', 48))));

        return response()->json($items);
    }

    public function favorite(Request $request, IptvItem $item): JsonResponse
    {
        abort_unless($request->user(), 401);

        $favorite = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('iptv_item_id', $item->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json(['favorited' => false]);
        }

        Favorite::query()->create([
            'user_id' => $request->user()->id,
            'iptv_item_id' => $item->id,
        ]);

        return response()->json(['favorited' => true]);
    }

    public function history(Request $request, IptvItem $item): JsonResponse
    {
        WatchHistory::query()->updateOrCreate(
            [
                'user_id' => $request->user()?->id,
                'iptv_item_id' => $item->id,
            ],
            [
                'watched_at' => now(),
                'progress_seconds' => max(0, $request->integer('progress_seconds')),
            ]
        );

        return response()->json(['saved' => true]);
    }

    private function itemQuery(Request $request)
    {
        return IptvItem::query()
            ->visible()
            ->published()
            ->with('category')
            ->where('is_adult', false)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->orderBy('name');
    }
}

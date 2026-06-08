<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IptvItem;
use App\Services\PublicLiveTvService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveTvController extends Controller
{
    public function __invoke(Request $request, PublicLiveTvService $liveTv): View
    {
        $query = $liveTv->query(
            $request->string('category')->toString(),
            $request->string('search')->toString(),
        );
        $totalCount = (clone $query)->count();
        $items = $liveTv->channels($query);
        $initialChannels = $items
            ->map(fn (IptvItem $item): array => $liveTv->serialize($item))
            ->values();

        $this->logCatalog($query, $items, $totalCount);

        return view('public.live', compact('totalCount', 'initialChannels'));
    }

    private function logCatalog(Builder $query, $items, int $totalCount): void
    {
        if (! app()->isLocal()) {
            return;
        }

        Log::debug('live-tv.route', [
            'route' => 'live-tv',
            'count' => $totalCount,
            'returned' => $items->count(),
            'filters' => [
                'type' => IptvItem::TYPE_LIVE,
                'is_active' => true,
                'is_public' => true,
                'is_adult' => false,
                'source_required' => true,
                'sports_only' => false,
                'playlist_approval_required' => false,
            ],
            'sql' => $query->toSql(),
            'first_names' => $items->take(3)->pluck('name')->values()->all(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IptvItem;
use App\Services\PublicLiveTvService;
use App\Support\StreamUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicTvController extends Controller
{
    public function __construct(
        private readonly PublicLiveTvService $liveTv,
    ) {}

    public function channels(Request $request): JsonResponse
    {
        $query = $this->liveTv->query(
            $request->string('category')->toString(),
            $request->string('search')->toString(),
        );
        $total = (clone $query)->count();
        $items = $this->liveTv->channels($query);
        $channels = $items
            ->map(fn (IptvItem $item): array => $this->liveTv->serialize($item))
            ->values();

        $this->logCatalog($query, $items, $total);

        return response()->json([
            'success' => true,
            'count' => $channels->count(),
            'total' => $total,
            'channels' => $channels,
        ]);
    }

    public function show(IptvItem $item): JsonResponse
    {
        $this->abortUnlessPublicLive($item);

        $item->load('category:id,name');

        if (app()->isLocal()) {
            Log::debug('live-tv.api.show', [
                'endpoint' => '/api/tv/channels/{item}',
                'channel_id' => $item->id,
                'channel_name' => $item->name,
                'filters' => ['public_live' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'channel' => $this->liveTv->serialize($item, includePlayUrl: true),
        ]);
    }

    public function playUrl(IptvItem $item): JsonResponse
    {
        $this->abortUnlessPublicLive($item);
        $item->load(['sources' => fn ($query) => $query->where('is_active', true)->orderBy('priority')]);

        $sources = $item->sources
            ->map(fn ($source): array => [
                'id' => $source->id,
                'label' => $source->label,
                'type' => $source->type,
                'quality' => $source->quality_label,
                'priority' => $source->priority,
                'health_status' => $source->health_status,
                'url' => StreamUrl::iptvItemSourceBridge($source->id),
            ])
            ->values();

        if ($sources->isEmpty()) {
            $sources->push([
                'id' => null,
                'label' => $item->qualityLabel() === 'Auto' ? 'Primary' : $item->qualityLabel(),
                'type' => $item->stream_type ?: $item->extension ?: 'auto',
                'quality' => $item->qualityLabel(),
                'priority' => 1,
                'health_status' => $item->health_status,
                'url' => StreamUrl::iptvItemBridge($item->id),
            ]);
        }

        return response()->json([
            'success' => true,
            'url' => $sources->first()['url'],
            'sources' => $sources,
            'expires_in' => 1800,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->liveTv->channels($this->liveTv->query())
            ->map(fn (IptvItem $item): string => $item->category?->name ?: $item->group_title ?: 'General')
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'count' => $categories->count(),
            'categories' => $categories,
        ]);
    }

    private function logCatalog(Builder $query, $items, int $total): void
    {
        if (! app()->isLocal()) {
            return;
        }

        Log::debug('live-tv.api.index', [
            'endpoint' => '/api/tv/channels',
            'count' => $total,
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

    private function abortUnlessPublicLive(IptvItem $item): void
    {
        abort_unless(
            IptvItem::query()->publicLive()->whereKey($item->getKey())->exists(),
            404
        );
    }
}

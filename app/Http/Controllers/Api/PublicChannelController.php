<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Support\StreamUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();
        $perPage = min(120, max(12, $request->integer('per_page', 80)));

        $cacheKey = 'public-api-channels:'.md5(json_encode([
            'search' => $search,
            'category' => $category,
            'per_page' => $perPage,
            'page' => $request->integer('page', 1),
        ]));

        $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($search, $category, $perPage): array {
            $channels = $this->baseQuery()
                ->with(['playlist', 'streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])
                ->when($category !== '', fn (Builder $query) => $query->where('group_title', $category))
                ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderByDesc('is_featured')
                ->orderBy('featured_rank')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($perPage);

            return [
                'data' => $channels->getCollection()
                    ->map(fn (Channel $channel) => $this->serialize($channel))
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

    public function show(Channel $channel): JsonResponse
    {
        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            404
        );

        $payload = Cache::remember("public-api-channel:{$channel->id}", now()->addMinutes(3), function () use ($channel): array {
            $channel->load([
                'playlist',
                'streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority'),
            ]);

            return $this->serialize($channel);
        });

        return response()->json(['data' => $payload]);
    }

    private function baseQuery(): Builder
    {
        return Channel::query()
            ->where('is_active', true)
            ->whereHas('playlist', fn (Builder $query) => $query
                ->where('is_public', true)
                ->whereNotNull('approved_at'));
    }

    private function serialize(Channel $channel): array
    {
        $source = $channel->active_stream_sources->first();

        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'stream_url' => $source['url'] ?? StreamUrl::proxied($channel->stream_url),
            'stream_type' => $source['type'] ?? $channel->stream_type ?? 'hls',
            'category' => $channel->group_title ?: 'General',
            'description' => ($channel->group_title ?: 'Live TV').' stream from '.($channel->playlist?->name ?? 'approved playlist').'.',
        ];
    }
}

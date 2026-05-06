<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public (no-auth) API for the Live TV split-screen UI.
 *
 * Channels are fetched from playlists that are both public AND approved,
 * so no sensitive or unapproved data is ever exposed.
 */
class PublicTvController extends Controller
{
    private function publicChannelBase(): Builder
    {
        return Channel::query()
            ->where('is_active', true)
            ->whereHas('playlist', fn (Builder $q) => $q
                ->where('is_public', true)
                ->whereNotNull('approved_at'));
    }

    /** GET /api/tv/channels */
    public function channels(Request $request): JsonResponse
    {
        $category = $request->string('category')->toString();
        $search   = $request->string('search')->toString();
        $perPage  = min(100, max(20, $request->integer('per_page', 60)));

        $cacheKey = 'api-tv:channels:'.md5(json_encode([
            'category' => $category,
            'search' => $search,
            'per_page' => $perPage,
            'page' => $request->integer('page', 1),
        ]));

        $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($category, $search, $perPage): array {
            $channels = $this->publicChannelBase()
                ->with(['streams' => fn ($q) => $q->where('is_active', true)->orderBy('priority')])
                ->when($category !== '' && $category !== '__ALL__',
                    fn ($q) => $q->where('group_title', $category))
                ->when($search !== '',
                    fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($perPage);

            return [
                'data' => $channels->getCollection()
                    ->map(fn (Channel $ch) => $this->serializeChannel($ch))
                    ->values(),
                'meta' => [
                    'current_page' => $channels->currentPage(),
                    'last_page'    => $channels->lastPage(),
                    'total'        => $channels->total(),
                ],
            ];
        });

        return response()->json($payload);
    }

    /** GET /api/tv/channels/{channel} */
    public function show(Channel $channel): JsonResponse
    {
        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            404
        );

        $payload = Cache::remember("api-tv:channel:{$channel->id}", now()->addMinutes(3), function () use ($channel): array {
            $channel->load([
                'playlist',
                'streams' => fn ($q) => $q->where('is_active', true)->orderBy('priority'),
            ]);

            return $this->serializeChannel($channel);
        });

        return response()->json(['data' => $payload]);
    }

    /** GET /api/tv/categories */
    public function categories(): JsonResponse
    {
        $payload = Cache::remember('api-tv:categories', now()->addMinutes(10), function (): array {
            $total = $this->publicChannelBase()->count();

            $cats = $this->publicChannelBase()
                ->whereNotNull('group_title')
                ->selectRaw('group_title, COUNT(*) as cnt')
                ->groupBy('group_title')
                ->orderBy('group_title')
                ->pluck('cnt', 'group_title');

            return [
                'total'      => $total,
                'categories' => $cats,
            ];
        });

        return response()->json($payload);
    }

    private function serializeChannel(Channel $channel): array
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

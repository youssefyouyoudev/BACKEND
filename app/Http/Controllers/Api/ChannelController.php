<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChannelResource;
use App\Models\Channel;
use App\Services\ActivityLogService;
use App\Services\AppSettingsService;
use App\Services\StreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly AppSettingsService $settingsService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $channels = Channel::query()
            ->visibleTo($user)
            ->canonical()
            ->with('playlist')
            ->with(['favoredByUsers' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('group'), fn ($query) => $query->where('group_title', $request->string('group')->toString()))
            ->when($request->filled('playlist_id'), fn ($query) => $query->where('playlist_id', $request->integer('playlist_id')))
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->paginate(min(60, max(1, $request->integer('per_page', 24))));

        return ChannelResource::collection($channels);
    }

    public function featured(Request $request): JsonResponse
    {
        $user = $request->user();
        $groups = $this->settingsService->all()['homepage_featured_groups'] ?? [];

        $channels = Channel::query()
            ->visibleTo($user)
            ->canonical()
            ->with('playlist')
            ->with(['favoredByUsers' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('is_active', true)
            ->when($groups !== [], fn ($query) => $query->whereIn('group_title', $groups))
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->limit(18)
            ->get();

        return response()->json([
            'data' => ChannelResource::collection($channels),
        ]);
    }

    public function favorites(Request $request)
    {
        $user = $request->user();

        $channels = Channel::query()
            ->whereHas('favoredByUsers', fn ($query) => $query->where('user_id', $user->id))
            ->canonical()
            ->with('playlist')
            ->with(['favoredByUsers' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderBy('name')
            ->paginate(24);

        return ChannelResource::collection($channels);
    }

    public function show(Request $request, Channel $channel): JsonResponse
    {
        $this->authorize('view', $channel);

        $user = $request->user();

        $channel->load([
            'playlist',
            'favoredByUsers' => fn ($query) => $query->where('user_id', $user->id),
            'watchHistories' => fn ($query) => $query->where('user_id', $user->id),
        ]);

        $relatedChannels = Channel::query()
            ->visibleTo($user)
            ->canonical()
            ->with('playlist')
            ->with(['favoredByUsers' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('id', '!=', $channel->id)
            ->when($channel->group_title, fn ($query) => $query->where('group_title', $channel->group_title))
            ->limit(8)
            ->get();

        return response()->json([
            'channel' => new ChannelResource($channel),
            'related_channels' => ChannelResource::collection($relatedChannels),
        ]);
    }

    public function favorite(Channel $channel): JsonResponse
    {
        $this->authorize('view', $channel);

        $user = request()->user();

        $user->favorites()->syncWithoutDetaching([$channel->id]);
        $this->activityLogService->log($user, 'channel.favorited', $channel);

        return response()->json([
            'message' => 'Channel added to favorites.',
        ]);
    }

    public function unfavorite(Channel $channel): JsonResponse
    {
        $this->authorize('view', $channel);

        $user = request()->user();

        $user->favorites()->detach($channel->id);
        $this->activityLogService->log($user, 'channel.unfavorited', $channel);

        return response()->json([
            'message' => 'Channel removed from favorites.',
        ]);
    }

    /**
     * Public endpoint: return active stream sources for a channel.
     * Used by the TV player sidebar for channel switching + failover.
     */
    public function streams(Channel $channel, StreamService $streamService): JsonResponse
    {
        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            404
        );

        return response()->json([
            'id'      => $channel->id,
            'name'    => $channel->name,
            'logo'    => $channel->logo,
            'sources' => $streamService->sourcesFor($channel),
        ]);
    }
}

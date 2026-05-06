<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\WatchHistoryResource;
use App\Models\Channel;
use App\Models\Playlist;
use App\Services\AppSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AppSettingsService $settingsService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $this->settingsService->all();
        $featuredGroups = $settings['homepage_featured_groups'] ?? [];

        $featuredChannels = Channel::query()
            ->visibleTo($user)
            ->with('playlist')
            ->with(['favoredByUsers' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('is_active', true)
            ->when($featuredGroups !== [], fn ($query) => $query->whereIn('group_title', $featuredGroups))
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->limit(12)
            ->get();

        $recentPlaylists = Playlist::query()
            ->where('user_id', $user->id)
            ->withCount('channels')
            ->latest()
            ->limit(5)
            ->get();

        $watchHistory = $user->watchHistories()
            ->with(['channel.playlist', 'channel.favoredByUsers' => fn ($query) => $query->where('user_id', $user->id)])
            ->latest('watched_at')
            ->limit(8)
            ->get();

        return response()->json([
            'stats' => [
                'playlists' => $user->playlists()->count(),
                'channels' => Channel::query()->whereHas('playlist', fn ($query) => $query->where('user_id', $user->id))->count(),
                'favorites' => $user->favorites()->count(),
                'recently_watched' => $user->watchHistories()->count(),
            ],
            'settings' => $settings,
            'featured_channels' => ChannelResource::collection($featuredChannels),
            'recent_playlists' => PlaylistResource::collection($recentPlaylists),
            'continue_watching' => WatchHistoryResource::collection($watchHistory),
        ]);
    }
}

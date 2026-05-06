<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Channel;
use App\Models\Playlist;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $recentActivity = ActivityLog::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'users' => User::query()->count(),
                'active_users' => User::query()->where('is_active', true)->count(),
                'playlists' => Playlist::query()->count(),
                'pending_playlists' => Playlist::query()->where('is_public', true)->whereNull('approved_at')->count(),
                'channels' => Channel::query()->count(),
                'watch_events' => WatchHistory::query()->count(),
            ],
            'recent_activity' => ActivityLogResource::collection($recentActivity),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelStream;
use App\Models\Playlist;
use App\Models\WorldCupMatch;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'playlists' => Playlist::query()->count(),
            'channels' => Channel::query()->count(),
            'categories' => Channel::query()->whereNotNull('group_title')->distinct('group_title')->count('group_title'),
            'last_sync' => Playlist::query()->latest('last_synced_at')->value('last_synced_at'),
            'online_streams' => ChannelStream::query()->where('health_status', 'online')->count(),
            'offline_streams' => ChannelStream::query()->where('health_status', 'offline')->count(),
            'unknown_streams' => ChannelStream::query()->whereIn('health_status', ['unknown', 'unchecked'])->count(),
            'world_cup_matches' => WorldCupMatch::query()->count(),
            'world_cup_with_channel' => WorldCupMatch::query()
                ->where(fn ($query) => $query
                    ->whereHas('iptvItems')
                    ->orWhereNotNull('selected_iptv_item_id')
                    ->orWhereNotNull('selected_channel_id'))
                ->count(),
            'world_cup_missing_channel' => WorldCupMatch::query()
                ->whereDoesntHave('iptvItems')
                ->whereNull('selected_iptv_item_id')
                ->whereNull('selected_channel_id')
                ->whereNull('channel_name_manual')
                ->count(),
            'world_cup_missing_commentator' => WorldCupMatch::query()->whereNull('commentator')->count(),
            'world_cup_live_enabled' => WorldCupMatch::query()->where('is_live_link_enabled', true)->count(),
        ];

        $playlists = Playlist::query()
            ->withCount('channels')
            ->latest()
            ->paginate(10);

        $failedSources = ChannelStream::query()
            ->whereIn('health_status', ['offline', 'failed', 'timeout'])
            ->with('channel')
            ->latest('last_checked_at')
            ->limit(6)
            ->get();

        $nextWorldCupMatches = WorldCupMatch::query()
            ->with('selectedChannel')
            ->upcoming()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'playlists' => $playlists,
            'failedSources' => $failedSources,
            'nextWorldCupMatches' => $nextWorldCupMatches,
        ]);
    }
}

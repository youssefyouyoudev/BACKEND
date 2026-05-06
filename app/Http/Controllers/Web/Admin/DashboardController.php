<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Playlist;
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
        ];

        $playlists = Playlist::query()
            ->withCount('channels')
            ->latest()
            ->paginate(10);

        return view('admin.dashboard', [
            'stats' => $stats,
            'playlists' => $playlists,
        ]);
    }
}

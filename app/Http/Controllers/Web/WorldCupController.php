<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\WorldCupMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorldCupController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab', 'upcoming')->toString();

        $matches = WorldCupMatch::query()
            ->publicVisible()
            ->groupStage()
            ->with([
                'selectedChannel.playlist',
                'selectedChannel.category',
                'selectedIptvItem.playlist',
                'iptvItems.playlist',
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(fn (Builder $searchQuery) => $searchQuery
                    ->where('home_team', 'like', "%{$search}%")
                    ->orWhere('away_team', 'like', "%{$search}%"));
            })
            ->when($request->filled('group'), fn (Builder $query) => $query->where('group_name', $request->string('group')))
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate('morocco_kickoff_at', $request->string('date')->toString()))
            ->when($request->filled('channel'), function (Builder $query) use ($request): void {
                $channel = $request->string('channel')->trim()->toString();
                $query->where(function (Builder $channelQuery) use ($channel): void {
                    $channelQuery->where('selected_channel_id', $channel)
                        ->orWhere('channel_name_manual', 'like', "%{$channel}%");
                });
            })
            ->when($tab === 'today', fn (Builder $query) => $query->whereDate('morocco_kickoff_at', now('Africa/Casablanca')->toDateString()))
            ->when($tab === 'upcoming', fn (Builder $query) => $query->where('kickoff_at', '>=', now()))
            ->orderBy('kickoff_at')
            ->get();

        return view('public.world-cup', [
            'matches' => $matches,
            'tab' => $tab,
            'groups' => collect(range('A', 'L'))->map(fn (string $group): string => "Group {$group}"),
            'channels' => Channel::query()
                ->whereHas('worldCupMatches')
                ->orderBy('name')
                ->get(['id', 'name']),
            'dates' => WorldCupMatch::query()
                ->groupStage()
                ->whereNotNull('morocco_kickoff_at')
                ->orderBy('morocco_kickoff_at')
                ->get(['morocco_kickoff_at'])
                ->map(fn (WorldCupMatch $match): string => $match->morocco_kickoff_at->toDateString())
                ->unique()
                ->values(),
        ]);
    }
}

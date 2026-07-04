<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\WorldCupMatch;
use App\Services\FootballDayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorldCupController extends Controller
{
    public function __construct(private readonly FootballDayService $footballDayService) {}

    public function index(Request $request): View
    {
        $section = (string) $request->route('section', 'schedule');
        $tab = $request->string('tab', $section === 'groups' ? 'groups' : 'upcoming')->toString();
        [$todayStartUtc, $todayEndUtc] = $this->footballDayService->todayQueryRangeUtc();

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
            ->when($section === 'morocco', fn (Builder $query) => $query
                ->where(fn (Builder $teamQuery) => $teamQuery
                    ->where('home_team', 'Morocco')
                    ->orWhere('away_team', 'Morocco')))
            ->when($section === 'africa', fn (Builder $query) => $query
                ->where(fn (Builder $teamQuery) => $teamQuery
                    ->whereIn('home_team_code', ['MA', 'DZ', 'TN', 'EG', 'SN', 'GH', 'CI', 'ZA', 'CV', 'CD'])
                    ->orWhereIn('away_team_code', ['MA', 'DZ', 'TN', 'EG', 'SN', 'GH', 'CI', 'ZA', 'CV', 'CD'])))
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate('morocco_kickoff_at', $request->string('date')->toString()))
            ->when($request->filled('channel'), function (Builder $query) use ($request): void {
                $channel = $request->string('channel')->trim()->toString();
                $query->where(function (Builder $channelQuery) use ($channel): void {
                    $channelQuery->where('selected_channel_id', $channel)
                        ->orWhere('channel_name_manual', 'like', "%{$channel}%");
                });
            })
            ->when($tab === 'today', fn (Builder $query) => $query->whereBetween('kickoff_at', [$todayStartUtc, $todayEndUtc]))
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
            'section' => $section,
        ]);
    }

    public function knockout(): View
    {
        $stageOrder = $this->knockoutStageOrder();

        $matches = WorldCupMatch::query()
            ->publicVisible()
            ->knockout()
            ->with([
                'selectedChannel.playlist',
                'selectedChannel.category',
                'selectedIptvItem.playlist',
                'iptvItems.playlist',
            ])
            ->orderBy('kickoff_at')
            ->orderBy('match_number')
            ->get()
            ->sortBy(fn (WorldCupMatch $match): string => sprintf(
                '%02d-%s-%03d',
                array_search($match->stage, $stageOrder, true),
                $match->kickoff_at?->format('YmdHis') ?? '99999999999999',
                $match->match_number
            ))
            ->values();

        $matchesByStage = $matches
            ->groupBy('stage')
            ->sortBy(fn (Collection $_, string $stage): int => array_search($stage, $stageOrder, true));

        return view('world-cup.knockout', [
            'matches' => $matches,
            'matchesByStage' => $matchesByStage,
            'stageOrder' => $stageOrder,
            'schema' => $this->knockoutSchema($matches),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function knockoutStageOrder(): array
    {
        return [
            'round_of_32',
            'round_of_16',
            'quarter_final',
            'semi_final',
            'third_place',
            'final',
        ];
    }

    private function knockoutSchema(Collection $matches): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => $matches
                ->map(fn (WorldCupMatch $match): array => [
                    '@type' => 'SportsEvent',
                    'name' => "{$match->home_display_name} vs {$match->away_display_name}",
                    'startDate' => $match->kickoff_at?->toIso8601String(),
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'url' => route('matches.watch', $match),
                    'location' => [
                        '@type' => 'Place',
                        'name' => $match->venue,
                        'address' => collect([$match->city, $match->country])->filter()->implode(', '),
                    ],
                    'organizer' => [
                        '@type' => 'Organization',
                        'name' => 'FIFA World Cup 2026',
                    ],
                ])
                ->values()
                ->all(),
        ];
    }
}

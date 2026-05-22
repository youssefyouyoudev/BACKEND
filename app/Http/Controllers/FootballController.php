<?php

namespace App\Http\Controllers;

use App\Services\ChannelMatcherService;
use App\Services\TheSportsDbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FootballController extends Controller
{
    public function __construct(private readonly TheSportsDbService $sportsDb)
    {
    }

    public function index(): View
    {
        return view('football.index', [
            'leagues' => config('football_leagues.top_leagues', []),
        ]);
    }

    public function today(): JsonResponse
    {
        return $this->matchesResponse($this->sportsDb->getTopLeagueMatchesByDate(now()->toDateString()));
    }

    public function byDate(Request $request): JsonResponse
    {
        $date = $this->parseDate($request->string('date')->toString());

        return $this->matchesResponse($this->sportsDb->getTopLeagueMatchesByDate($date));
    }

    public function upcoming(): JsonResponse
    {
        return $this->matchesResponse($this->sportsDb->getUpcomingTopLeagueMatches());
    }

    public function results(): JsonResponse
    {
        return $this->matchesResponse($this->sportsDb->getRecentTopLeagueResults());
    }

    public function event(string $eventId): View|JsonResponse
    {
        $event = $this->sportsDb->getEventDetails($eventId);

        abort_unless($event, 404);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $event,
                'message' => null,
            ]);
        }

        return view('football.event', [
            'match' => $event,
        ]);
    }

    public function eventTv(string $eventId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->sportsDb->getEventTvChannels($eventId),
            'message' => null,
        ]);
    }

    public function matchChannelDebug(Request $request, ChannelMatcherService $matcher): JsonResponse
    {
        $name = $request->string('name')->toString();

        abort_if($name === '', 422, 'The name query parameter is required.');

        return response()->json($matcher->debugMatch(
            $name,
            $request->string('country')->toString() ?: null,
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     */
    private function matchesResponse(array $matches): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $matches,
            'message' => null,
        ]);
    }

    private function parseDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}

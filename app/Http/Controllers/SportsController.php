<?php

namespace App\Http\Controllers;

use App\Services\TheSportsDbService;
use Illuminate\Contracts\View\View;
use Throwable;

class SportsController extends Controller
{
    public function __invoke(TheSportsDbService $sportsDb): View
    {
        $matches = collect();

        try {
            $matches = collect($sportsDb->getTopLeagueMatchesByDate(now()->toDateString()))->take(6);
        } catch (Throwable) {
            $matches = collect();
        }

        return view('sports.index', [
            'leagues' => collect(config('football_leagues.top_leagues', [])),
            'matches' => $matches,
        ]);
    }
}

<?php

use App\Models\WorldCupMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the clean football and world cup routes with safe metadata', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertSuccessful()
        ->assertSee('RiFiTV')
        ->assertDontSee('free live stream', false)
        ->assertDontSee('IPTV', false)
        ->assertDontSee('m3u8', false);
})->with([
    'football.today',
    'football.tomorrow',
    'football.results',
    'football.schedules',
    'world-cup-2026.index',
    'world-cup-2026.schedule',
    'world-cup-2026.groups',
    'world-cup-2026.morocco',
    'world-cup-2026.africa',
    'tv-guide.index',
    'news.index',
]);

it('keeps match coverage available but out of the search index', function () {
    $match = WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'competition' => 'World Cup 2026',
        'kickoff_at' => now()->addHour(),
        'morocco_kickoff_at' => now()->addHour(),
    ]);

    $this->get(route('matches.watch', $match))
        ->assertSuccessful()
        ->assertSee('Match Center - Morocco vs Brazil | RiFiTV')
        ->assertSee('<meta name="robots" content="noindex,follow">', false)
        ->assertDontSee('free live stream', false)
        ->assertDontSee('IPTV', false);
});

it('keeps private playback paths out of the public sitemap', function () {
    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee(route('football.today'), false)
        ->assertSee(route('world-cup-2026.schedule'), false)
        ->assertSee(route('tv-guide.index'), false)
        ->assertDontSee('/watch/', false)
        ->assertDontSee('/match/', false)
        ->assertDontSee('/live-tv', false);
});

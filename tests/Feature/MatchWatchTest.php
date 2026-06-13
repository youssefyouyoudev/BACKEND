<?php

use App\Models\IptvItem;
use App\Models\Playlist;
use App\Models\WorldCupMatch;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('uses the exact one hour before and three hours after watch window', function (string $now, string $status) {
    $match = WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'kickoff_at' => '2026-06-13 19:00:00',
        'morocco_kickoff_at' => '2026-06-13 20:00:00',
    ]);

    $at = CarbonImmutable::parse($now, WorldCupMatch::MOROCCO_TIMEZONE);

    expect($match->watchStatus($at))->toBe($status)
        ->and($match->isWatchOpen($at))->toBe($status === 'open');
})->with([
    '18:59 is upcoming' => ['2026-06-13 18:59:00', 'opens_soon'],
    '19:00 is open' => ['2026-06-13 19:00:00', 'open'],
    '20:00 is open' => ['2026-06-13 20:00:00', 'open'],
    '22:00 is open' => ['2026-06-13 22:00:00', 'open'],
    '23:00 is open' => ['2026-06-13 23:00:00', 'open'],
    '23:01 is expired' => ['2026-06-13 23:01:00', 'expired'],
]);

it('does not show yesterday matches in the homepage today section', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 12:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    WorldCupMatch::query()->create([
        'home_team' => 'Yesterday Home',
        'away_team' => 'Yesterday Away',
        'kickoff_at' => '2026-06-12 19:00:00',
        'morocco_kickoff_at' => '2026-06-12 20:00:00',
    ]);
    WorldCupMatch::query()->create([
        'home_team' => 'Today Home',
        'away_team' => 'Today Away',
        'kickoff_at' => '2026-06-13 19:00:00',
        'morocco_kickoff_at' => '2026-06-13 20:00:00',
    ]);
    WorldCupMatch::query()->create([
        'home_team' => 'Tomorrow Home',
        'away_team' => 'Tomorrow Away',
        'kickoff_at' => '2026-06-14 19:00:00',
        'morocco_kickoff_at' => '2026-06-14 20:00:00',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Today Home')
        ->assertDontSee('Yesterday Home')
        ->assertDontSee('Tomorrow Home');
});

it('filters inactive and expired match IPTV assignments', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 20:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $playlist = Playlist::factory()->create(['is_public' => true, 'approved_at' => now()]);
    $active = createMatchIptvItem($playlist, 'Active HD');
    $inactive = createMatchIptvItem($playlist, 'Inactive HD');
    $expired = createMatchIptvItem($playlist, 'Expired HD');
    $match = createOpenMatch();

    $match->iptvItems()->attach($active->id, ['is_active' => true, 'priority' => 0]);
    $match->iptvItems()->attach($inactive->id, ['is_active' => false, 'priority' => 1]);
    $match->iptvItems()->attach($expired->id, [
        'is_active' => true,
        'priority' => 2,
        'expires_at' => '2026-06-13 18:59:00',
    ]);

    expect($match->fresh()->load('iptvItems.playlist')->availableWatchItems()->pluck('id')->all())
        ->toBe([$active->id]);
});

it('orders recommended match streams first and exposes channel and server metadata', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 20:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $playlist = Playlist::factory()->create(['is_public' => true, 'approved_at' => now()]);
    $primary = createMatchIptvItem($playlist, 'beIN Sports Max 1 HD');
    $backup = createMatchIptvItem($playlist, 'Alkass Sports HD');
    $match = createOpenMatch();

    $match->iptvItems()->attach($primary->id, [
        'is_active' => true,
        'priority' => 20,
        'channel_name' => 'beIN Sports Max 1',
        'server_label' => 'Server 2',
        'quality' => 'FHD',
        'language' => 'Arabic',
        'is_recommended' => true,
        'health_status' => 'online',
    ]);
    $match->iptvItems()->attach($backup->id, [
        'is_active' => true,
        'priority' => 1,
        'channel_name' => 'Alkass Sports',
        'server_label' => 'Server 1',
        'quality' => 'HD',
    ]);

    $freshMatch = $match->fresh()->load('iptvItems.playlist');

    expect($freshMatch->availableWatchItems()->pluck('id')->all())
        ->toBe([$primary->id, $backup->id]);

    $this->get(route('matches.watch', $match))
        ->assertSuccessful()
        ->assertSee('data-match-player-config', false)
        ->assertSee('beIN Sports Max 1')
        ->assertSee('Alkass Sports')
        ->assertSee('Server 2')
        ->assertSee('"recommended":true', false)
        ->assertSee('\/watch-link\/'.$match->id.'\/'.$primary->id.'\/play', false);
});

it('hides the player before and after the watch window', function () {
    config()->set('ads.enabled', true);
    $match = createOpenMatch();

    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 18:59:00', WorldCupMatch::MOROCCO_TIMEZONE));
    $this->get(route('matches.watch', $match))
        ->assertSuccessful()
        ->assertSee('Watch page opens at')
        ->assertSee('data-ad-placement="match_watch_before_content"', false)
        ->assertSee('data-ad-placement="match_watch_under_content"', false)
        ->assertSee('n6wxm.com/vignette.min.js', false)
        ->assertDontSee('data-rifi-video-player', false);

    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 23:01:00', WorldCupMatch::MOROCCO_TIMEZONE));
    $this->get(route('matches.watch', $match))
        ->assertSuccessful()
        ->assertSee('This match has ended.')
        ->assertDontSee('data-rifi-video-player', false);
});

it('rejects a direct signed match stream link after expiry', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 20:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $playlist = Playlist::factory()->create(['is_public' => true, 'approved_at' => now()]);
    $item = createMatchIptvItem($playlist, 'Protected HD');
    $match = createOpenMatch();
    $match->iptvItems()->attach($item->id);

    $url = URL::temporarySignedRoute(
        'matches.watch-link',
        $match->watch_expires_at,
        ['worldCupMatch' => $match, 'item' => $item],
        absolute: false,
    );

    $this->get($url)->assertRedirect();

    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 23:01:00', WorldCupMatch::MOROCCO_TIMEZONE));
    $this->get($url)->assertForbidden();
});

it('protects the match-scoped play endpoint with a relative signature', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-13 20:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $playlist = Playlist::factory()->create(['is_public' => true, 'approved_at' => now()]);
    $item = createMatchIptvItem($playlist, 'Signed FHD');
    $match = createOpenMatch();
    $match->iptvItems()->attach($item->id);

    $url = URL::temporarySignedRoute(
        'watch-links.play',
        $match->watch_expires_at,
        ['worldCupMatch' => $match, 'item' => $item],
        absolute: false,
    );

    $this->get($url)->assertRedirect();
    $this->get(parse_url($url, PHP_URL_PATH))->assertForbidden();
});

function createOpenMatch(): WorldCupMatch
{
    return WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'kickoff_at' => '2026-06-13 19:00:00',
        'morocco_kickoff_at' => '2026-06-13 20:00:00',
        'is_live_link_enabled' => true,
        'broadcast_status' => 'scheduled',
    ]);
}

function createMatchIptvItem(Playlist $playlist, string $name): IptvItem
{
    return IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => $name,
        'stream_url' => 'https://streams.example.com/'.str($name)->slug().'.m3u8',
        'extension' => 'm3u8',
        'is_active' => true,
        'is_public' => true,
        'is_adult' => false,
    ]);
}

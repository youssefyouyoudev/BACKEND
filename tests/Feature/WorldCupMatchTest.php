<?php

use App\Models\Channel;
use App\Models\IptvItem;
use App\Models\Playlist;
use App\Models\User;
use App\Models\WorldCupMatch;
use App\Support\TeamFlag;
use Carbon\CarbonImmutable;
use Database\Seeders\WorldCup2026GroupStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('has a local national flag for every seeded team', function () {
    $this->seed(WorldCup2026GroupStageSeeder::class);

    $teams = WorldCupMatch::query()
        ->get(['home_team', 'away_team'])
        ->flatMap(fn (WorldCupMatch $match) => [$match->home_team, $match->away_team])
        ->unique();

    expect($teams)->toHaveCount(48);

    foreach ($teams as $team) {
        $code = TeamFlag::code($team);

        expect($code)->not->toBeNull("No flag mapping exists for {$team}")
            ->and(public_path("images/flags/{$code}.svg"))->toBeFile();
    }
});

it('seeds all group stage matches without duplicates and preserves admin fields', function () {
    $this->seed(WorldCup2026GroupStageSeeder::class);

    $match = WorldCupMatch::query()->where('match_number', 2)->firstOrFail();
    $match->update([
        'commentator' => 'Admin Commentator',
        'channel_name_manual' => 'Admin Channel',
        'is_live_link_enabled' => true,
    ]);

    $this->seed(WorldCup2026GroupStageSeeder::class);

    expect(WorldCupMatch::query()->count())->toBe(72)
        ->and($match->fresh()->commentator)->toBe('Admin Commentator')
        ->and($match->fresh()->channel_name_manual)->toBe('Admin Channel')
        ->and($match->fresh()->is_live_link_enabled)->toBeTrue();
});

it('allows an admin to assign an existing channel and edit the commentator', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $playlist = Playlist::factory()->create(['is_public' => true, 'approved_at' => now()]);
    $channel = Channel::factory()->for($playlist)->create([
        'name' => 'beIN Sports MAX 1 FHD',
        'slug' => 'bein-sports-max-1',
        'is_active' => true,
    ]);
    $match = WorldCupMatch::query()->create([
        'match_number' => 1,
        'home_team' => 'Mexico',
        'away_team' => 'South Africa',
        'kickoff_at' => now()->addDay(),
        'broadcast_status' => 'to_confirm',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.world-cup-matches.update', $match), [
            'match_number' => 1,
            'competition' => 'FIFA World Cup 2026',
            'stage' => 'Group Stage',
            'group_name' => 'Group A',
            'home_team' => 'Mexico',
            'away_team' => 'South Africa',
            'kickoff_at' => now()->addDay()->toDateTimeString(),
            'selected_channel_id' => $channel->id,
            'commentator' => 'Hafid Derradji',
            'broadcast_status' => 'scheduled',
            'use_manual_live_url' => '1',
            'live_url_manual' => 'https://example.com/live.m3u8',
            'is_live_link_enabled' => '1',
        ])
        ->assertRedirect();

    expect($match->fresh()->selected_channel_id)->toBe($channel->id)
        ->and($match->fresh()->commentator)->toBe('Hafid Derradji')
        ->and($match->fresh()->is_live_link_enabled)->toBeTrue();
});

it('shows the selected channel publicly and links to the dedicated match page', function () {
    $playlist = Playlist::factory()->create(['is_public' => true, 'approved_at' => now()]);
    $channel = Channel::factory()->for($playlist)->create([
        'name' => 'RiFi Sports HD',
        'slug' => 'rifi-sports-hd',
        'is_active' => true,
    ]);
    $match = WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'stage' => 'Group Stage',
        'kickoff_at' => now()->addDay(),
        'morocco_kickoff_at' => now()->addDay(),
        'selected_channel_id' => $channel->id,
        'is_live_link_enabled' => false,
        'broadcast_status' => 'scheduled',
    ]);

    $this->get(route('world-cup.index', ['tab' => 'all']))
        ->assertSuccessful()
        ->assertSee('RiFi Sports')
        ->assertSee('images/flags/ma.svg', false)
        ->assertSee('images/flags/br.svg', false)
        ->assertSee('Match details')
        ->assertSee(route('matches.watch', $match), false)
        ->assertDontSee(route('channels.show', $channel->slug), false);

    $match->update(['is_live_link_enabled' => true]);

    $this->get(route('world-cup.index', ['tab' => 'all']))
        ->assertSuccessful()
        ->assertSee(route('matches.watch', $match), false)
        ->assertDontSee(route('channels.show', $channel->slug), false);
});

it('filters the admin list to matches missing a channel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $playlist = Playlist::factory()->create();
    $channel = Channel::factory()->for($playlist)->create();

    WorldCupMatch::query()->create([
        'home_team' => 'Missing Channel Team',
        'away_team' => 'Opponent One',
        'kickoff_at' => now()->addDay(),
    ]);
    WorldCupMatch::query()->create([
        'home_team' => 'Assigned Channel Team',
        'away_team' => 'Opponent Two',
        'kickoff_at' => now()->addDays(2),
        'selected_channel_id' => $channel->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.world-cup-matches.index', ['missing_channel' => 1]))
        ->assertSuccessful()
        ->assertSee('Missing Channel Team')
        ->assertDontSee('Assigned Channel Team');
});

it('searches and assigns only public active IPTV items with ajax', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $approvedPlaylist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $privatePlaylist = Playlist::factory()->create([
        'is_public' => false,
        'approved_at' => null,
    ]);
    $publicItem = IptvItem::query()->create([
        'playlist_id' => $approvedPlaylist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'beIN Sports World Cup',
        'stream_url' => 'https://streams.example.com/bein.m3u8',
        'is_active' => true,
        'is_public' => true,
        'is_adult' => false,
    ]);
    $secondPublicItem = IptvItem::query()->create([
        'playlist_id' => $approvedPlaylist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arryadia World Cup',
        'stream_url' => 'https://streams.example.com/arryadia.m3u8',
        'is_active' => true,
        'is_public' => true,
        'is_adult' => false,
    ]);
    $privateItem = IptvItem::query()->create([
        'playlist_id' => $privatePlaylist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Private Sports',
        'stream_url' => 'https://streams.example.com/private.m3u8',
        'is_active' => true,
        'is_public' => true,
        'is_adult' => false,
    ]);
    $match = WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'kickoff_at' => now()->addHour(),
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.world-cup-matches.iptv-items', ['q' => 'Sports']))
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'beIN Sports World Cup'])
        ->assertJsonMissing(['name' => 'Private Sports']);

    $this->actingAs($admin)
        ->patchJson(route('admin.world-cup-matches.assign-iptv-item', $match), [
            'iptv_item_id' => $privateItem->id,
        ])
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->patchJson(route('admin.world-cup-matches.assign-iptv-item', $match), [
            'iptv_item_id' => $publicItem->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('assignments.0.id', $publicItem->id)
        ->assertJsonPath('is_watch_window_open', true);

    $this->actingAs($admin)
        ->patchJson(route('admin.world-cup-matches.assign-iptv-item', $match), [
            'iptv_item_id' => $secondPublicItem->id,
        ])
        ->assertSuccessful()
        ->assertJsonCount(2, 'assignments');

    expect($match->fresh()->iptvItems()->pluck('iptv_items.id')->all())
        ->toEqualCanonicalizing([$publicItem->id, $secondPublicItem->id])
        ->and($match->fresh()->is_live_link_enabled)->toBeTrue();

    $this->actingAs($admin)
        ->patchJson(route('admin.world-cup-matches.assign-iptv-item', $match), [
            'iptv_item_id' => $publicItem->id,
        ])
        ->assertSuccessful()
        ->assertJsonCount(1, 'assignments')
        ->assertJsonMissing(['id' => $publicItem->id]);

    expect($match->fresh()->iptvItems()->pluck('iptv_items.id')->all())
        ->toBe([$secondPublicItem->id]);

    $this->actingAs($admin)
        ->patchJson(route('admin.world-cup-matches.assign-iptv-item', $match), [
            'iptv_item_id' => null,
        ])
        ->assertSuccessful()
        ->assertJsonCount(0, 'assignments');

    expect($match->fresh()->iptvItems)->toBeEmpty()
        ->and($match->fresh()->is_live_link_enabled)->toBeFalse();
});

it('unlocks assigned IPTV items one hour before kickoff', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-11 11:59:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'RiFi World Cup Live',
        'stream_url' => 'https://streams.example.com/world-cup.m3u8',
        'is_active' => true,
        'is_public' => true,
        'is_adult' => false,
    ]);
    $secondItem = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'RiFi World Cup Alternate',
        'stream_url' => 'https://streams.example.com/world-cup-alt.m3u8',
        'is_active' => true,
        'is_public' => true,
        'is_adult' => false,
    ]);
    $match = WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'kickoff_at' => '2026-06-11 13:00:00',
        'morocco_kickoff_at' => '2026-06-11 13:00:00',
        'is_live_link_enabled' => true,
    ]);
    $match->iptvItems()->attach([$item->id, $secondItem->id]);

    expect($match->fresh()->public_watch_links)->toBeEmpty();

    Carbon::setTestNow(CarbonImmutable::parse('2026-06-11 12:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $availableItems = $match->fresh()->load('iptvItems.playlist')->availableWatchItems();

    expect($availableItems)->toHaveCount(2);

    $this->get(route('world-cup.index', ['tab' => 'all']))
        ->assertSuccessful()
        ->assertSee('Match details')
        ->assertSee(route('matches.watch', $match), false)
        ->assertDontSee(route('watch.item', $item), false)
        ->assertDontSee(route('watch.item', $secondItem), false);
});

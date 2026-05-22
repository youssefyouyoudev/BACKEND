<?php

use App\Models\Channel;
use App\Models\Playlist;
use App\Services\ChannelMatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('matches thesportsdb channel names to active public playlist channels', function () {
    ChannelMatcherService::clearCache();

    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);

    $channel = Channel::factory()->for($playlist)->create([
        'name' => 'beIN Sports 1 HD',
        'slug' => 'bein-sports-1-hd',
        'country' => 'Morocco',
        'aliases' => ['beIN Sports MENA 1'],
        'is_active' => true,
    ]);

    $result = app(ChannelMatcherService::class)->enrichTvChannelsWithPlaylistLinks([
        ['channel' => 'beIN Sports HD 1', 'country' => 'Morocco'],
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['available'])->toBeTrue()
        ->and($result[0]['matched_channel_id'])->toBe($channel->id)
        ->and($result[0]['watch_url'])->toBe('/watch/bein-sports-1-hd');
});

it('leaves low confidence channel matches unavailable', function () {
    ChannelMatcherService::clearCache();

    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);

    Channel::factory()->for($playlist)->create([
        'name' => 'DAZN 1',
        'slug' => 'dazn-1',
        'is_active' => true,
    ]);

    $result = app(ChannelMatcherService::class)->enrichTvChannelsWithPlaylistLinks([
        ['channel' => 'Sky Sports Main Event', 'country' => 'United Kingdom'],
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['available'])->toBeFalse()
        ->and($result[0]['matched_channel_id'])->toBeNull()
        ->and($result[0]['watch_url'])->toBeNull();
});

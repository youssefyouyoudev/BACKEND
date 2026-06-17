<?php

use App\Models\IptvItem;
use App\Models\IptvItemSource;
use App\Models\Playlist;
use App\Models\User;
use App\Support\StreamUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('streaming.enable_external_streams', true);
    config()->set('streaming.allowed_stream_domains', ['streams.example.com']);
    config()->set('streaming.resolve_dns', false);
});

it('stores provider credentials and backup source urls encrypted', function () {
    $playlist = Playlist::factory()->create([
        'username' => 'provider-user',
        'password' => 'provider-pass',
    ]);
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arena 1 HD',
        'stream_url' => 'https://streams.example.com/legacy.m3u8',
        'is_active' => true,
        'is_public' => true,
    ]);
    $source = $item->sources()->create([
        'label' => 'Backup HD',
        'url' => 'https://streams.example.com/backup.m3u8',
        'type' => 'hls',
        'quality_label' => 'HD',
        'priority' => 2,
    ]);

    expect(DB::table('playlists')->whereKey($playlist->id)->value('username'))
        ->not->toBe('provider-user')
        ->and(DB::table('iptv_item_sources')->whereKey($source->id)->value('url'))
        ->not->toBe('https://streams.example.com/backup.m3u8')
        ->and($playlist->fresh()->username)->toBe('provider-user')
        ->and($source->fresh()->url)->toBe('https://streams.example.com/backup.m3u8');
});

it('returns ordered signed sources without exposing provider urls', function () {
    $playlist = Playlist::factory()->create();
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arena 1 FHD',
        'normalized_name' => 'arena 1',
        'stream_url' => 'https://streams.example.com/primary.m3u8',
        'stream_type' => 'hls',
        'quality_label' => 'FHD',
        'is_active' => true,
        'is_public' => true,
    ]);
    $item->sources()->createMany([
        [
            'label' => 'Primary FHD',
            'url' => 'https://streams.example.com/primary.m3u8',
            'type' => 'hls',
            'quality_label' => 'FHD',
            'priority' => 1,
        ],
        [
            'label' => 'Backup SD',
            'url' => 'https://streams.example.com/backup.ts',
            'type' => 'mpegts',
            'quality_label' => 'SD',
            'priority' => 2,
        ],
    ]);

    $response = $this->getJson("/api/tv/channels/{$item->id}/play-url")
        ->assertSuccessful()
        ->assertJsonPath('sources.0.label', 'Primary FHD')
        ->assertJsonPath('sources.1.label', 'Backup SD');

    expect($response->content())
        ->not->toContain('streams.example.com')
        ->and($response->json('sources.0.url'))->toContain('/play/iptv-source/');
});

it('bridges only a stored signed source record', function () {
    $playlist = Playlist::factory()->create();
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arena Protected',
        'stream_url' => 'https://streams.example.com/legacy.m3u8',
        'is_active' => true,
        'is_public' => true,
    ]);
    $source = $item->sources()->create([
        'label' => 'Primary',
        'url' => 'https://streams.example.com/master.m3u8',
        'type' => 'hls',
        'priority' => 1,
    ]);

    Http::fake([
        'https://streams.example.com/master.m3u8' => Http::response("#EXTM3U\n#EXTINF:6,\nsegment.ts"),
    ]);

    $this->get("/play/iptv-source/{$source->id}")->assertForbidden();

    $this->get(StreamUrl::iptvItemSourceBridge($source->id))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl');
});

it('forwards byte ranges through a stored signed source', function () {
    $playlist = Playlist::factory()->create();
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arena MPEGTS',
        'stream_url' => 'https://streams.example.com/legacy.ts',
        'is_active' => true,
        'is_public' => true,
    ]);
    $source = $item->sources()->create([
        'label' => 'Primary TS',
        'url' => 'https://streams.example.com/channel.ts',
        'type' => 'mpegts',
        'priority' => 1,
    ]);

    Http::fake([
        'https://streams.example.com/channel.ts' => Http::response(
            'transport-stream-data',
            206,
            [
                'Content-Type' => 'video/mp2t',
                'Content-Range' => 'bytes 0-20/100',
                'Accept-Ranges' => 'bytes',
            ],
        ),
    ]);

    $this->withHeader('Range', 'bytes=0-20')
        ->get(StreamUrl::iptvItemSourceBridge($source->id))
        ->assertStatus(206)
        ->assertHeader('Content-Range', 'bytes 0-20/100');

    Http::assertSent(fn ($request) => $request->hasHeader('Range', 'bytes=0-20'));
});

it('lets an admin update a channel and add a validated backup', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $playlist = Playlist::factory()->create();
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arena 1 HD',
        'stream_url' => 'https://streams.example.com/legacy.m3u8',
        'is_active' => true,
        'is_public' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.iptv-items.update', $item), [
            'name' => 'Arena 1 FHD',
            'quality_label' => 'FHD',
            'stream_type' => 'hls',
            'is_active' => '1',
            'is_public' => '1',
            'is_featured' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.iptv-items.sources.store', $item), [
            'label' => 'Backup 1',
            'url' => 'https://streams.example.com/backup.m3u8',
            'type' => 'hls',
            'quality_label' => 'HD',
            'priority' => 2,
            'is_active' => '1',
        ])
        ->assertRedirect();

    $source = IptvItemSource::query()->where('iptv_item_id', $item->id)->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.iptv-items.sources.update', [$item, $source]), [
            'label' => 'Primary HD',
            'type' => 'hls',
            'quality_label' => 'HD',
            'priority' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect($item->fresh())
        ->name->toBe('Arena 1 FHD')
        ->normalized_name->toBe('arena 1')
        ->is_featured->toBeTrue()
        ->and($source->fresh()->label)->toBe('Primary HD')
        ->and($source->fresh()->priority)->toBe(1);
});

it('shows player diagnostics only to admins when debug mode is off', function () {
    config()->set('app.debug', false);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->get(route('live-tv'))
        ->assertSuccessful()
        ->assertDontSee('Player debug');

    $this->actingAs($admin)
        ->get(route('live-tv'))
        ->assertSuccessful()
        ->assertSee('Player debug');
});

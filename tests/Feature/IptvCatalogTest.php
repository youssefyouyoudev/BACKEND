<?php

use App\Models\Favorite;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('validates and imports get php and gets php m3u urls into the iptv catalog', function (string $url) {
    Http::fake([
        $url => Http::response(<<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="live-1" tvg-logo="https://cdn.example.com/logo.png" group-title="Sports",Live One
https://streams.example.com/live/u/p/1.ts
M3U),
    ]);

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Provider Pack',
            'input_type' => Playlist::INPUT_TYPE_M3U_URL,
            'm3u_url' => $url,
        ])
        ->assertRedirect('/admin/playlists');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->status)->toBe('active');
    expect($playlist->imported_channels_count)->toBe(1);
    expect(IptvItem::query()->where('name', 'Live One')->exists())->toBeTrue();
})->with([
    'get php' => ['https://example.com/get.php?username=USERNAME&password=PASSWORD&type=m3u_plus&output=mpegts'],
    'gets php aliases' => ['https://example.com/in/gets.php?user=USERNAME&pass=PASSWORD&t=m3uplus&o=mpegts'],
]);

it('imports an uploaded m3u file into iptv items', function () {
    Storage::fake('playlists');

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $file = UploadedFile::fake()->createWithContent('vod.m3u', <<<'M3U'
#EXTM3U
#EXTINF:-1 group-title="Movies",Movie One
https://streams.example.com/movie/u/p/10.mp4
M3U);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Upload Pack',
            'input_type' => Playlist::INPUT_TYPE_UPLOAD,
            'playlist_file' => $file,
        ])
        ->assertRedirect('/admin/playlists');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->imported_movies_count)->toBe(1);
    expect(IptvItem::query()->where('type', 'movie')->where('name', 'Movie One')->exists())->toBeTrue();
});

it('saves active code only sources as needs url', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Code Pack',
            'input_type' => Playlist::INPUT_TYPE_ACTIVE_CODE,
            'active_code' => 'ABC12345',
        ])
        ->assertRedirect('/admin');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->status)->toBe('needs_url');
    expect($playlist->active_code)->toBe('ABC12345');
    expect(IptvItem::query()->count())->toBe(0);
});

it('reimports a playlist and replaces old iptv items', function () {
    Http::fake([
        'https://example.com/list.m3u' => Http::sequence()
            ->push("#EXTM3U\n#EXTINF:-1 group-title=\"Live\",Old\nhttps://streams.example.com/old.ts")
            ->push("#EXTM3U\n#EXTINF:-1 group-title=\"Live\",New\nhttps://streams.example.com/new.ts"),
    ]);

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($admin)->post('/admin/playlists', [
        'name' => 'Reimport Pack',
        'input_type' => Playlist::INPUT_TYPE_M3U_URL,
        'm3u_url' => 'https://example.com/list.m3u',
    ]);

    $playlist = Playlist::query()->firstOrFail();

    $this->actingAs($admin)
        ->post("/admin/playlists/{$playlist->id}/reimport")
        ->assertRedirect();

    expect(IptvItem::query()->where('name', 'Old')->exists())->toBeFalse();
    expect(IptvItem::query()->where('name', 'New')->exists())->toBeTrue();
});

it('toggles favorites and searches iptv items', function () {
    $user = User::factory()->create(['is_active' => true]);
    $playlist = Playlist::factory()->create();
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => 'live',
        'name' => 'Searchable Channel',
        'stream_url' => 'https://streams.example.com/live.m3u8',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post("/watch/item/{$item->id}/favorite")
        ->assertRedirect();

    expect(Favorite::query()->where('iptv_item_id', $item->id)->exists())->toBeTrue();

    $this->get('/api/watch/search?q=Searchable')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Searchable Channel');
});

it('blocks adult categories without parental unlock', function () {
    $playlist = Playlist::factory()->create();
    $category = IptvCategory::query()->create([
        'playlist_id' => $playlist->id,
        'type' => 'live',
        'name' => 'Adult',
    ]);

    $this->get("/watch/category/{$category->id}")
        ->assertForbidden();
});

it('builds live tv from active public IPTV items without requiring a sports category or approved playlist', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => false,
        'approved_at' => null,
    ]);
    $category = IptvCategory::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvCategory::TYPE_LIVE,
        'name' => 'Documentary',
    ]);
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'category_id' => $category->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Nature World HD',
        'stream_url' => 'https://streams.example.com/live.m3u8',
        'extension' => 'm3u8',
        'is_active' => true,
    ]);

    $this->get('/live-tv')
        ->assertSuccessful()
        ->assertSee('Documentary')
        ->assertSee('Nature World HD')
        ->assertSee('fifa_world_cup_2026_tease.png')
        ->assertSee('2026-06-11T20:00:00+01:00')
        ->assertSee('Reconnecting...');

    $this->getJson('/api/tv/channels?category=Documentary')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('channels.0.id', $item->id)
        ->assertJsonPath('channels.0.category', 'Documentary')
        ->assertJsonPath('channels.0.quality', 'HD')
        ->assertJsonPath('channels.0.watch_url', route('watch.item', $item))
        ->assertJsonPath('channels.0.playback_status.playable', true);

    $response = $this->getJson("/api/tv/channels/{$item->id}")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('channel.name', 'Nature World HD')
        ->assertJsonPath('channel.playback_status.playable', true)
        ->assertJsonPath('channel.playback_status.code', 'ready');

    expect($response->json('channel.public_play_url'))
        ->toContain("/play/iptv/{$item->id}")
        ->not->toContain('streams.example.com')
        ->and($response->content())->not->toContain($item->stream_url);

    $this->getJson("/api/tv/channels/{$item->id}/play-url")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('expires_in', 1800)
        ->assertJson(fn ($json) => $json
            ->whereType('url', 'string')
            ->etc()
        );
});

it('uses a local logo fallback without returning the provider source URL', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arryadia HD',
        'stream_url' => 'http://unapproved.example/live.ts',
        'logo' => 'http://images.example/arryadia.png',
        'extension' => 'mpegts',
        'is_active' => true,
    ]);

    $this->getJson("/api/tv/channels/{$item->id}")
        ->assertSuccessful()
        ->assertJsonPath('channel.logo', asset('brand/rifi-logo.png'))
        ->assertJsonPath('channel.playback_status.playable', true)
        ->assertJsonMissing(['source_url' => $item->stream_url]);
});

it('exposes public live channels from every category', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);

    $bein = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'beIN Sports 2 HD',
        'stream_url' => 'https://streams.example.com/bein.m3u8',
        'is_active' => true,
    ]);
    $arryadia = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arryadia HD',
        'stream_url' => 'https://streams.example.com/arryadia.m3u8',
        'is_active' => true,
    ]);
    $abuDhabi = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Abu Dhabi Sports 1',
        'stream_url' => 'https://streams.example.com/ad-sports.m3u8',
        'is_active' => true,
    ]);
    $other = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Other Sports HD',
        'stream_url' => 'https://streams.example.com/other.m3u8',
        'is_active' => true,
    ]);

    $this->getJson('/api/tv/channels')
        ->assertSuccessful()
        ->assertJsonPath('count', 4)
        ->assertJsonFragment(['id' => $bein->id])
        ->assertJsonFragment(['id' => $arryadia->id])
        ->assertJsonFragment(['id' => $abuDhabi->id])
        ->assertJsonFragment(['id' => $other->id]);

    $this->getJson("/api/tv/channels/{$other->id}")
        ->assertSuccessful();
});

it('searches the public live TV catalog without sports-only matching', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);

    IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'History Documentary HD',
        'stream_url' => 'https://streams.example.com/history.m3u8',
        'is_active' => true,
    ]);

    $this->getJson('/api/tv/channels?search=Documentary')
        ->assertSuccessful()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('channels.0.name', 'History Documentary HD');
});

it('does not expose inactive private or non-live IPTV items on live TV', function () {
    $privatePlaylist = Playlist::factory()->create();
    $publicPlaylist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);

    $privateLiveItem = IptvItem::query()->create([
        'playlist_id' => $privatePlaylist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Private Live',
        'stream_url' => 'https://streams.example.com/private.m3u8',
        'is_active' => true,
        'is_public' => false,
    ]);
    IptvItem::query()->create([
        'playlist_id' => $publicPlaylist->id,
        'type' => IptvItem::TYPE_MOVIE,
        'name' => 'Public Movie',
        'stream_url' => 'https://streams.example.com/movie.mp4',
        'is_active' => true,
    ]);

    $this->getJson('/api/tv/channels')
        ->assertSuccessful()
        ->assertJsonPath('count', 0);

    $this->getJson("/api/tv/channels/{$privateLiveItem->id}")
        ->assertNotFound();
});

it('uses an item-based protected play URL for raw HTTP MPEG-TS streams', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'beIN Sports 1 HD',
        'stream_url' => 'http://streams.example.com/live/channel.ts',
        'extension' => 'mpegts',
        'is_active' => true,
    ]);

    $channel = $this->getJson("/api/tv/channels/{$item->id}")
        ->assertSuccessful()
        ->json('channel');

    expect($channel['public_play_url'])
        ->toContain("/play/iptv/{$item->id}")
        ->not->toContain('streams.example.com');
});

it('returns current iptv items after the catalog changes', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $old = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arryadia HD',
        'stream_url' => 'https://streams.example.com/old.m3u8',
        'is_active' => true,
    ]);

    $this->getJson('/api/tv/channels')
        ->assertSuccessful()
        ->assertJsonPath('channels.0.name', 'Arryadia HD');

    $old->delete();
    IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Arryadia FHD',
        'stream_url' => 'https://streams.example.com/new.m3u8',
        'is_active' => true,
    ]);

    $this->getJson('/api/tv/channels')
        ->assertSuccessful()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('channels.0.name', 'Arryadia FHD');
});

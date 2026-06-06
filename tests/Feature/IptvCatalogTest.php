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

it('builds live tv from public approved iptv items and categories', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $category = IptvCategory::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvCategory::TYPE_LIVE,
        'name' => 'IPTV Sports',
    ]);
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'category_id' => $category->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'IPTV Sports HD',
        'stream_url' => 'https://streams.example.com/live.m3u8',
        'extension' => 'm3u8',
        'is_active' => true,
    ]);

    $this->get('/live-tv')
        ->assertSuccessful()
        ->assertSee('IPTV Sports')
        ->assertSee('IPTV Sports HD')
        ->assertSee(route('watch.item', $item));

    $this->getJson('/api/tv/channels?category=IPTV%20Sports')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $item->id)
        ->assertJsonPath('data.0.group_title', 'IPTV Sports')
        ->assertJsonPath('data.0.watch_url', route('watch.item', $item))
        ->assertJsonPath('meta.total', 1);

    $this->getJson("/api/tv/channels/{$item->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'IPTV Sports HD')
        ->assertJsonCount(1, 'data.sources');
});

it('does not expose private or non-live iptv items on live tv', function () {
    $privatePlaylist = Playlist::factory()->create([
        'is_public' => false,
        'approved_at' => null,
    ]);
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
        ->assertJsonPath('meta.total', 0);

    $this->getJson("/api/tv/channels/{$privateLiveItem->id}")
        ->assertNotFound();
});

<?php

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores and parses an uploaded playlist file from the admin dashboard', function () {
    Storage::fake('playlists');
    config()->set('queue.default', 'sync');

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $playlistFile = UploadedFile::fake()->createWithContent('sports.m3u', <<<'M3U'
#EXTM3U playlist-name="Arena Pack"
#EXTINF:-1 group-title="Sports",Arena 1
https://streams.example.com/arena-1.m3u8
#EXTINF:-1 group-title="Sports",Arena 2
https://streams.example.com/arena-2.m3u8
M3U);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Arena Pack',
            'input_type' => Playlist::INPUT_TYPE_UPLOAD_FILE,
            'playlist_file' => $playlistFile,
        ])
        ->assertRedirect();

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->source_type)->toBe(Playlist::SOURCE_TYPE_FILE);
    expect($playlist->input_type)->toBe(Playlist::INPUT_TYPE_UPLOAD_FILE);
    expect($playlist->file_path)->not->toBeNull();
    Storage::disk('playlists')->assertExists($playlist->file_path);

    $this->actingAs($admin)
        ->post("/admin/playlists/{$playlist->id}/parse")
        ->assertRedirect();

    $playlist->refresh();

    expect($playlist->status)->toBe('active');
    expect($playlist->last_synced_at)->not->toBeNull();
    expect($playlist->channels()->count())->toBe(2);
});

it('stores and parses a remote playlist url from the admin dashboard', function () {
    config()->set('queue.default', 'sync');

    Http::fake([
        'https://example.com/admin-pack.m3u' => Http::response(<<<'M3U'
#EXTM3U playlist-name="Admin Pack"
#EXTINF:-1 tvg-logo="https://cdn.example.com/news.png" group-title="News",Admin News
https://streams.example.com/admin-news.m3u8
#EXTINF:-1 group-title="Kids",Admin Kids
https://streams.example.com/admin-kids.m3u8
M3U),
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Admin Pack',
            'input_type' => Playlist::INPUT_TYPE_REMOTE_URL,
            'm3u_url' => 'https://example.com/admin-pack.m3u',
        ])
        ->assertRedirect('/admin/playlists');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->source_type)->toBe(Playlist::SOURCE_TYPE_URL);
    expect($playlist->input_type)->toBe(Playlist::INPUT_TYPE_REMOTE_URL);

    $this->actingAs($admin)
        ->post("/admin/playlists/{$playlist->id}/parse")
        ->assertRedirect();

    $playlist->refresh();

    expect($playlist->status)->toBe('active');
    expect($playlist->last_synced_at)->not->toBeNull();
    expect($playlist->channels()->count())->toBe(2);
});

it('accepts an iptv get php url with query parameters from the admin dashboard', function () {
    config()->set('queue.default', 'sync');

    $url = 'https://example.com/get.php?username=USER&password=PASS&type=m3u_plus&output=mpegts';

    Http::fake([
        $url => Http::response(<<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="sports-1" group-title="Sports",Query Sports
https://streams.example.com/live/USER/PASS/12345
M3U),
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Query Pack',
            'input_type' => Playlist::INPUT_TYPE_REMOTE_URL,
            'm3u_url' => $url,
        ])
        ->assertRedirect();

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->status)->toBe('active');
    expect($playlist->m3u_url)->toBe($url);
    expect($playlist->channels()->count())->toBe(1);
});

it('accepts an xtream server outside the streaming domain allowlist', function () {
    config()->set('streaming.enable_external_streams', false);
    config()->set('streaming.allowed_playlist_domains', ['approved.example.com']);

    Http::fake([
        'http://unlisted-xtream.test/*' => Http::response([]),
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Unlisted Xtream',
            'input_type' => Playlist::INPUT_TYPE_XTREAM,
            'server_url' => 'http://unlisted-xtream.test',
            'username' => 'demo-user',
            'password' => 'demo-pass',
            'output' => 'mpegts',
        ])
        ->assertRedirect('/admin/playlists')
        ->assertSessionHasNoErrors();

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->server_url)->toBe('http://unlisted-xtream.test');
    expect($playlist->status)->toBe('active');
});

it('saves an active code only playlist without parsing until a url or file is available', function () {
    config()->set('queue.default', 'sync');

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Activation Pack',
            'input_type' => Playlist::INPUT_TYPE_ACTIVE_CODE,
            'active_code' => '696966207988',
        ])
        ->assertRedirect();

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->input_type)->toBe(Playlist::INPUT_TYPE_ACTIVE_CODE);
    expect($playlist->active_code)->toBe('696966207988');
    expect($playlist->m3u_url)->toBeNull();
    expect($playlist->status)->toBe('needs_url');
    expect($playlist->channels()->count())->toBe(0);
});

it('updates a playlist source and reparses it from the admin dashboard', function () {
    config()->set('queue.default', 'sync');

    Http::fake([
        'https://example.com/original.m3u' => Http::response(<<<'M3U'
#EXTM3U
#EXTINF:-1 group-title="Sports",Original Sports
https://streams.example.com/original.m3u8
M3U),
        'https://example.com/replacement.m3u' => Http::response(<<<'M3U'
#EXTM3U
#EXTINF:-1 group-title="News",Replacement News
https://streams.example.com/replacement-news.m3u8
#EXTINF:-1 group-title="Kids",Replacement Kids
https://streams.example.com/replacement-kids.m3u8
M3U),
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Original Pack',
            'input_type' => Playlist::INPUT_TYPE_REMOTE_URL,
            'm3u_url' => 'https://example.com/original.m3u',
        ])
        ->assertRedirect('/admin/playlists');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->channels()->count())->toBe(1);

    $this->actingAs($admin)
        ->put("/admin/playlists/{$playlist->id}", [
            'name' => 'Replacement Pack',
            'input_type' => Playlist::INPUT_TYPE_REMOTE_URL,
            'm3u_url' => 'https://example.com/replacement.m3u',
        ])
        ->assertRedirect('/admin/playlists');

    $playlist->refresh();

    expect($playlist->name)->toBe('Replacement Pack');
    expect($playlist->source_url)->toBe('https://example.com/replacement.m3u');
    expect($playlist->status)->toBe('active');
    expect($playlist->channels()->count())->toBe(2);
    expect($playlist->channels()->where('name', 'Original Sports')->exists())->toBeFalse();
});

it('renames an uploaded playlist and reparses its existing file', function () {
    Storage::fake('playlists');
    config()->set('queue.default', 'sync');

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $playlistFile = UploadedFile::fake()->createWithContent('rename-me.m3u', <<<'M3U'
#EXTM3U
#EXTINF:-1 group-title="Sports",Rename Sports
https://streams.example.com/rename-sports.m3u8
M3U);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Rename Me',
            'input_type' => Playlist::INPUT_TYPE_UPLOAD_FILE,
            'playlist_file' => $playlistFile,
        ])
        ->assertRedirect('/admin/playlists');

    $playlist = Playlist::query()->firstOrFail();
    $filePath = $playlist->file_path;

    $this->actingAs($admin)
        ->put("/admin/playlists/{$playlist->id}", [
            'name' => 'Renamed File Pack',
            'input_type' => Playlist::INPUT_TYPE_UPLOAD_FILE,
        ])
        ->assertRedirect('/admin/playlists');

    $playlist->refresh();

    expect($playlist->name)->toBe('Renamed File Pack');
    expect($playlist->source_type)->toBe(Playlist::SOURCE_TYPE_FILE);
    expect($playlist->file_path)->toBe($filePath);
    expect($playlist->status)->toBe('active');
    expect($playlist->channels()->count())->toBe(1);
    Storage::disk('playlists')->assertExists($filePath);
});

it('deletes a playlist, its channels, and uploaded file from the admin dashboard', function () {
    Storage::fake('playlists');
    config()->set('queue.default', 'sync');

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $playlistFile = UploadedFile::fake()->createWithContent('delete-me.m3u', <<<'M3U'
#EXTM3U
#EXTINF:-1 group-title="Sports",Delete Sports
https://streams.example.com/delete-sports.m3u8
M3U);

    $this->actingAs($admin)
        ->post('/admin/playlists', [
            'name' => 'Delete Me',
            'input_type' => Playlist::INPUT_TYPE_UPLOAD_FILE,
            'playlist_file' => $playlistFile,
        ])
        ->assertRedirect('/admin/playlists');

    $playlist = Playlist::query()->firstOrFail();
    $filePath = $playlist->file_path;

    Storage::disk('playlists')->assertExists($filePath);
    expect($playlist->channels()->count())->toBe(1);

    $this->actingAs($admin)
        ->delete("/admin/playlists/{$playlist->id}")
        ->assertRedirect('/admin/playlists');

    expect(Playlist::query()->whereKey($playlist->id)->exists())->toBeFalse();
    Storage::disk('playlists')->assertMissing($filePath);
});

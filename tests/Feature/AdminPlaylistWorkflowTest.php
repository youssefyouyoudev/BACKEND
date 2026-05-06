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
            'playlist_file' => $playlistFile,
        ])
        ->assertRedirect('/admin');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->source_type)->toBe(Playlist::SOURCE_TYPE_FILE);
    expect($playlist->file_path)->not->toBeNull();
    Storage::disk('playlists')->assertExists($playlist->file_path);

    $this->actingAs($admin)
        ->post("/admin/playlists/{$playlist->id}/parse")
        ->assertRedirect('/admin');

    $playlist->refresh();

    expect($playlist->status)->toBe('completed');
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
            'm3u_url' => 'https://example.com/admin-pack.m3u',
        ])
        ->assertRedirect('/admin');

    $playlist = Playlist::query()->firstOrFail();

    expect($playlist->source_type)->toBe(Playlist::SOURCE_TYPE_URL);

    $this->actingAs($admin)
        ->post("/admin/playlists/{$playlist->id}/parse")
        ->assertRedirect('/admin');

    $playlist->refresh();

    expect($playlist->status)->toBe('completed');
    expect($playlist->last_synced_at)->not->toBeNull();
    expect($playlist->channels()->count())->toBe(2);
});

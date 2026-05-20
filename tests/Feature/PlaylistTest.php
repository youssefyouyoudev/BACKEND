<?php

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('uploads and parses a playlist file for the authenticated user', function () {
    Storage::fake('playlists');

    $user = User::factory()->create();
    $playlistContent = <<<'M3U'
#EXTM3U playlist-name="Demo Legal"
#EXTINF:-1 tvg-id="news-1" tvg-name="RiFi News" tvg-logo="/logos/news.png" group-title="News",RiFi News
https://streams.example.com/news.m3u8
#EXTINF:-1 tvg-id="sports-1" group-title="Sports",RiFi Sports
https://streams.example.com/sports.m3u8
M3U;

    $file = UploadedFile::fake()->createWithContent('demo.m3u', $playlistContent);

    $response = $this->actingAs($user)->postJson('/api/playlists/upload', [
        'name' => 'My Legal Playlist',
        'playlist_file' => $file,
        'is_public' => false,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('playlist.name', 'My Legal Playlist')
        ->assertJsonPath('playlist.channels.0.name', 'RiFi News');

    $playlist = Playlist::query()->first();

    expect($playlist)->not->toBeNull();
    expect($playlist->channels()->count())->toBe(2);
    expect($playlist->status)->toBe('completed');
    expect($playlist->last_synced_at)->not->toBeNull();
});

it('imports and parses a playlist url for the authenticated user', function () {
    Http::fake([
        'https://example.com/demo.m3u' => Http::response(<<<'M3U'
#EXTM3U playlist-name="Remote Demo"
#EXTINF:-1 tvg-id="news-1" group-title="News",Remote News
https://streams.example.com/news.m3u8
#EXTINF:-1 tvg-id="kids-1" group-title="Kids",Remote Kids
https://streams.example.com/kids.m3u8
M3U),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/playlists/url', [
        'name' => 'Remote Playlist',
        'source_url' => 'https://example.com/demo.m3u',
        'is_public' => false,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('playlist.name', 'Remote Playlist')
        ->assertJsonPath('playlist.channels.0.name', 'Remote News');

    $playlist = Playlist::query()->first();

    expect($playlist)->not->toBeNull();
    expect($playlist->channels()->count())->toBe(2);
    expect($playlist->status)->toBe('completed');
    expect($playlist->last_synced_at)->not->toBeNull();
});

it('groups duplicate channel names into one channel with multiple server sources', function () {
    Storage::fake('playlists');

    $user = User::factory()->create();
    $playlistContent = <<<'M3U'
#EXTM3U playlist-name="Duplicate Demo"
#EXTINF:-1 tvg-logo="https://img.example.com/bein.png" group-title="Sports",beIN Sports 1
http://server-a.example.com/live/bein1.ts
#EXTINF:-1 tvg-logo="https://img.example.com/bein-alt.png" group-title="Other Sports",  beIN   Sports 1
http://server-b.example.com/live/bein1.ts
#EXTINF:-1 group-title="Sports",beIN Sports 1
http://server-b.example.com/live/bein1.ts
M3U;

    $file = UploadedFile::fake()->createWithContent('duplicates.m3u', $playlistContent);

    $response = $this->actingAs($user)->postJson('/api/playlists/upload', [
        'name' => 'Duplicate Playlist',
        'playlist_file' => $file,
        'is_public' => false,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('playlist.channels.0.name', 'beIN Sports 1')
        ->assertJsonCount(1, 'playlist.channels')
        ->assertJsonCount(2, 'playlist.channels.0.sources')
        ->assertJsonPath('playlist.channels.0.sources.0.label', 'Server 1')
        ->assertJsonPath('playlist.channels.0.sources.1.label', 'Server 2');

    $playlist = Playlist::query()->with('channels.streams')->firstOrFail();

    expect($playlist->channels)->toHaveCount(1);
    expect($playlist->channels->first()->streams)->toHaveCount(2);
});

it('does not allow a user to view another users private playlist', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $playlist = Playlist::factory()->for($owner)->create([
        'is_public' => false,
        'approved_at' => null,
    ]);

    $this->actingAs($viewer)
        ->getJson("/api/playlists/{$playlist->id}")
        ->assertForbidden();
});

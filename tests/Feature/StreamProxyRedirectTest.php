<?php

use App\Support\StreamUrl;
use App\Models\Channel;
use App\Models\ChannelStream;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('requires a valid temporary signature before redirecting streams', function () {
    $url = 'https://example.com/live/master.m3u8';

    $this->get('/stream/'.StreamUrl::encodeProxyUrl($url))
        ->assertForbidden();

    $this->get(StreamUrl::signedRedirect($url))
        ->assertRedirect($url);
});

it('redirects insecure stream urls instead of proxying bytes through php', function () {
    $url = 'http://example.com/live/channel.ts';

    $this->get(StreamUrl::signedRedirect($url))
        ->assertRedirect($url);
});

it('rejects invalid encoded stream urls', function () {
    $this->get(URL::temporarySignedRoute('stream.proxy', now()->addMinutes(5), [
        'encodedUrl' => 'not-valid!!!!',
    ]))
        ->assertBadRequest();
});

it('rejects decoded values that are not valid urls', function () {
    $this->get(StreamUrl::signedRedirect('not a url'))
        ->assertBadRequest();
});

it('rejects unsupported stream url schemes', function () {
    $this->get(StreamUrl::signedRedirect('ftp://example.com/live.ts'))
        ->assertBadRequest();
});

it('redirects approved channel sources by signed channel route', function () {
    $playlist = Playlist::factory()->create([
        'is_public' => true,
        'approved_at' => now(),
    ]);

    $channel = Channel::factory()->for($playlist)->create([
        'stream_url' => 'https://primary.example.com/live.m3u8',
    ]);

    $stream = ChannelStream::query()->create([
        'channel_id' => $channel->id,
        'stream_url' => 'http://backup.example.com/live.ts',
        'stream_hash' => sha1('http://backup.example.com/live.ts'),
        'stream_type' => 'mpegts',
        'priority' => 1,
        'is_active' => true,
        'label' => 'Backup',
    ]);

    $this->get(StreamUrl::channelRedirect($channel->id, $stream->id))
        ->assertRedirect('http://backup.example.com/live.ts');
});

it('bridges hls playlists through signed same-origin urls for browser playback', function () {
    $url = 'http://example.com/live/master.m3u8';

    Http::fake([
        $url => Http::response(<<<'M3U'
#EXTM3U
#EXTINF:6,
segment-1.ts
M3U, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
        ]),
    ]);

    $content = $this->get(StreamUrl::signedBridge($url))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
        ->content();

    expect($content)->toContain('/bridge/');
    expect($content)->toContain('signature=');
    expect($content)->not->toContain('segment-1.ts');
});

it('generates https bridge urls when production https forcing is enabled', function () {
    Config::set('rifimedia.force_https', true);

    $url = StreamUrl::channelBridge(383, 383);

    expect($url)->toStartWith('https://');
});

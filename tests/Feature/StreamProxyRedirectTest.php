<?php

use App\Models\Channel;
use App\Models\ChannelStream;
use App\Models\IptvItem;
use App\Models\Playlist;
use App\Support\StreamUrl;
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
    ], absolute: false))
        ->assertBadRequest();
});

it('validates protected stream signatures independently of the public host and scheme', function () {
    $url = 'https://example.com/live/master.m3u8';
    $signedUrl = StreamUrl::signedRedirect($url);

    expect($signedUrl)
        ->toStartWith('http')
        ->toContain('/stream/');

    $this->withServerVariables([
        'HTTP_HOST' => 'rifitv.com',
        'HTTPS' => 'on',
    ])->get($signedUrl)->assertRedirect($url);
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

it('generates host-independent bridge urls when production https forcing is enabled', function () {
    Config::set('rifimedia.force_https', true);

    $url = StreamUrl::channelBridge(383, 383);

    expect($url)
        ->toStartWith('http')
        ->toContain('/bridge-channel/383')
        ->toContain('signature=');
});

it('bridges hls and mpegts sources in the browser', function () {
    expect(StreamUrl::canBridgeInBrowser('http://example.com/live/master.m3u8', 'm3u8'))->toBeTrue()
        ->and(StreamUrl::canBridgeInBrowser('http://example.com/live/channel.ts', 'mpegts'))->toBeTrue()
        ->and(StreamUrl::canBridgeInBrowser('http://example.com/live/channel.mpegts'))->toBeTrue()
        ->and(StreamUrl::canBridgeInBrowser('http://example.com/live/channel', 'stream'))->toBeFalse();
});

it('streams mpegts bytes through the same origin bridge', function () {
    $url = 'http://example.com/live/channel.mpegts';

    Http::fake([
        $url => Http::response('mpegts-bytes', 200, ['Content-Type' => 'video/mp2t']),
    ]);

    $this->get(StreamUrl::signedBridge($url))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp2t')
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertStreamedContent('mpegts-bytes');
});

it('plays public IPTV items through a protected item route without exposing the source', function () {
    $playlist = Playlist::factory()->create();
    $sourceUrl = 'https://example.com/live/protected.m3u8';
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Protected Live HD',
        'stream_url' => $sourceUrl,
        'extension' => 'm3u8',
        'is_active' => true,
        'is_public' => true,
    ]);

    Http::fake([
        $sourceUrl => Http::response("#EXTM3U\n#EXTINF:6,\nsegment.ts", 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
        ]),
    ]);

    $playUrl = StreamUrl::iptvItemBridge($item->id);

    expect($playUrl)
        ->toStartWith('http')
        ->toContain("/play/iptv/{$item->id}")
        ->not->toContain('example.com');

    $content = $this->get($playUrl)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
        ->content();

    expect($content)
        ->toContain('/bridge/')
        ->not->toContain('segment.ts')
        ->not->toContain('example.com');
});

it('rejects protected playback for hidden IPTV items', function () {
    $playlist = Playlist::factory()->create();
    $item = IptvItem::query()->create([
        'playlist_id' => $playlist->id,
        'type' => IptvItem::TYPE_LIVE,
        'name' => 'Hidden Live',
        'stream_url' => 'https://example.com/live/hidden.m3u8',
        'is_active' => true,
        'is_public' => false,
    ]);

    $this->get(StreamUrl::iptvItemBridge($item->id))->assertNotFound();
});

it('answers protected IPTV preflight requests with streaming headers', function () {
    $this->options('/play/iptv/123')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeader('Access-Control-Allow-Headers', 'Range, Origin, Accept, Content-Type')
        ->assertHeader('X-Accel-Buffering', 'no')
        ->assertHeader('Accept-Ranges', 'bytes');
});

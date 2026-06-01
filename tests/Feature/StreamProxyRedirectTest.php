<?php

use App\Support\StreamUrl;
use Illuminate\Support\Facades\Http;

it('redirects secure stream urls instead of proxying bytes through php', function () {
    $url = 'https://example.com/live/master.m3u8';

    $this->get('/stream/'.StreamUrl::encodeProxyUrl($url))
        ->assertRedirect($url);
});

it('proxies insecure stream urls to avoid browser mixed content blocks', function () {
    $url = 'http://example.com/live/channel.ts';

    Http::fake([
        $url => Http::response('ts-bytes', 200, [
            'Content-Type' => 'video/mp2t',
        ]),
    ]);

    $this->get('/stream/'.StreamUrl::encodeProxyUrl($url).'?sig=invalid')
        ->assertForbidden();

    $response = $this->get(StreamUrl::proxied($url))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp2t');

    expect($response->streamedContent())->toBe('ts-bytes');
});

it('rewrites insecure hls playlist entries through the stream proxy', function () {
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

    $content = $this->get(StreamUrl::proxied($url))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
        ->content();

    expect($content)->toContain('/stream/');
    expect($content)->not->toContain('segment-1.ts');
});

it('rejects invalid encoded stream urls', function () {
    $this->get('/stream/not-valid!!!!')
        ->assertBadRequest();
});

it('rejects decoded values that are not valid urls', function () {
    $this->get('/stream/'.StreamUrl::encodeProxyUrl('not a url'))
        ->assertBadRequest();
});

it('rejects unsupported stream url schemes', function () {
    $this->get('/stream/'.StreamUrl::encodeProxyUrl('ftp://example.com/live.ts'))
        ->assertBadRequest();
});

<?php

use App\Support\StreamUrl;
use Tests\TestCase;

uses(TestCase::class);

it('rewrites hls playlist urls through signed stream redirects', function () {
    $playlist = StreamUrl::rewritePlaylist(
        <<<'M3U'
#EXTM3U
#EXT-X-KEY:METHOD=AES-128,URI="keys/live.key"
#EXT-X-STREAM-INF:BANDWIDTH=1280000
variant/main.m3u8
#EXTINF:6,
segment-1.ts
M3U,
        'https://service.example.com/live/master.m3u8'
    );

    expect($playlist)->toContain('/stream/');
    expect($playlist)->toContain('signature=');
    expect($playlist)->not->toContain('URI="keys/live.key"');
    expect($playlist)->not->toContain('variant/main.m3u8');
    expect($playlist)->not->toContain('segment-1.ts');
});

<?php

use App\Services\M3UParser;

it('parses m3u items with missing logo and group fields', function () {
    $items = app(M3UParser::class)->parse(<<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="one" tvg-name="One",One
https://streams.example.com/live/u/p/1.ts?token=abc
#EXTINF:-1,Movie One
https://streams.example.com/movie/u/p/2.mp4
#EXTINF:-1 tvg-logo="" group-title="",Series One
https://streams.example.com/series/u/p/3
M3U);

    expect($items)->toHaveCount(3);
    expect($items[0]['name'])->toBe('One');
    expect($items[0]['logo'])->toBeNull();
    expect($items[0]['group_title'])->toBeNull();
    expect($items[0]['type'])->toBe('live');
    expect($items[1]['type'])->toBe('movie');
    expect($items[2]['type'])->toBe('series');
});

<?php

use App\Services\PlaylistParserService;

it('parses channels, resolves relative assets, and skips duplicate streams', function () {
    $service = app(PlaylistParserService::class);

    $parsed = $service->parseContent(
        <<<'M3U'
#EXTM3U playlist-name="Legal Demo"
#EXTINF:-1 tvg-id="news-1" tvg-logo="/logo/news.png" group-title="News",<b>RiFi News</b>
channel/news.m3u8
#EXTINF:-1 tvg-id="news-1" tvg-logo="/logo/news.png" group-title="News",RiFi News Duplicate
channel/news.m3u8
#EXTINF:-1 tvg-id="kids-1" group-title="Kids",RiFi Kids
https://cdn.example.com/kids.m3u8
#EXTINF:-1 tvg-id="movie-1" group-title="Movies",RiFi Movies
http://cdn.example.com/movies.m3u8
M3U,
        'https://streams.example.com/playlists/main.m3u'
    );

    expect($parsed['title'])->toBe('Legal Demo');
    expect($parsed['entries'])->toHaveCount(3);
    expect($parsed['entries'][0]['logo'])->toBe('https://streams.example.com/logo/news.png');
    expect($parsed['entries'][0]['stream_url'])->toBe('https://streams.example.com/playlists/channel/news.m3u8');
    expect($parsed['entries'][0]['name'])->toBe('RiFi News');
    expect($parsed['entries'][2]['stream_url'])->toBe('https://cdn.example.com/movies.m3u8');
    expect($parsed['groups'])->toBe(['News', 'Kids', 'Movies']);
});

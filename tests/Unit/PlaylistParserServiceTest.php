<?php

use App\Models\Playlist;
use App\Services\PlaylistParserService;

it('parses channels, resolves relative assets, and preserves duplicate stream entries for import grouping', function () {
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
    expect($parsed['entries'])->toHaveCount(4);
    expect($parsed['entries'][0]['logo'])->toBe('https://streams.example.com/logo/news.png');
    expect($parsed['entries'][0]['stream_url'])->toBe('https://streams.example.com/playlists/channel/news.m3u8');
    expect($parsed['entries'][0]['name'])->toBe('RiFi News');
    expect($parsed['entries'][3]['stream_url'])->toBe('http://cdn.example.com/movies.m3u8');
    expect($parsed['groups'])->toBe(['News', 'Kids', 'Movies']);
});

it('parses channels with missing logo and group fields and flexible stream urls', function () {
    $service = app(PlaylistParserService::class);

    $parsed = $service->parseContent(
        <<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="dash-1",Dash Channel
https://streams.example.com/watch/manifest.mpd?token=abc
#EXTINF:-1,Extensionless Channel
https://streams.example.com/live/user/pass/12345
#EXTINF:-1 tvg-name="Transport Stream",Ignored display name
https://streams.example.com/channel.ts?session=abc
#EXTINF:-1 tvg-logo="" group-title="",MP4 Channel
https://streams.example.com/video.mp4
M3U
    );

    expect($parsed['entries'])->toHaveCount(4);
    expect($parsed['entries'][0]['name'])->toBe('Dash Channel');
    expect($parsed['entries'][0]['logo'])->toBeNull();
    expect($parsed['entries'][0]['group_title'])->toBeNull();
    expect($parsed['entries'][0]['stream_type'])->toBe('dash');
    expect($parsed['entries'][1]['stream_type'])->toBe('mpegts');
    expect($parsed['entries'][2]['name'])->toBe('Transport Stream');
    expect($parsed['entries'][3]['stream_type'])->toBe('mp4');
    expect($parsed['groups'])->toBe([]);
});

it('masks sensitive playlist url query parameters for display', function () {
    $masked = Playlist::maskSensitiveUrl('http://domain.com/get.php?user=abc&pass=secret&type=m3u_plus&token=xyz&output=mpegts');

    expect($masked)->toBe('http://domain.com/get.php?user=****&pass=****&type=m3u_plus&token=****&output=mpegts');
});

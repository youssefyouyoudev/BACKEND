<?php

use App\Services\PlaylistUrlBuilder;

it('builds xtream m3u and api urls', function () {
    $builder = app(PlaylistUrlBuilder::class);

    expect($builder->buildFromXtream('http://domain.com/', 'u', 'p', 'hls'))
        ->toBe('http://domain.com/get.php?username=u&password=p&type=m3u_plus&output=hls');

    expect($builder->buildXtreamApiUrl('http://domain.com', 'u', 'p', 'get_live_streams'))
        ->toBe('http://domain.com/player_api.php?username=u&password=p&action=get_live_streams');
});

it('normalizes provider aliases and masks sensitive query params', function () {
    $builder = app(PlaylistUrlBuilder::class);

    expect($builder->normalizeProviderUrl('http://domain.com/in/gets.php?user=U&pass=P&t=m3uplus&o=mpegts'))
        ->toBe('http://domain.com/in/gets.php?username=U&password=P&type=m3u_plus&output=mpegts');

    expect($builder->maskSensitiveUrl('http://domain.com/get.php?username=U&password=P&token=T&type=m3u_plus'))
        ->toBe('http://domain.com/get.php?username=****&password=****&token=****&type=m3u_plus');
});

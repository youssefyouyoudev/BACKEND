<?php

use App\Support\StreamUrl;

it('redirects valid stream urls instead of proxying bytes through php', function () {
    $url = 'https://example.com/live/master.m3u8';

    $this->get('/stream/'.StreamUrl::encodeProxyUrl($url))
        ->assertRedirect($url);
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

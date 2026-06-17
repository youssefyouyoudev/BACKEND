<?php

use App\Services\IptvChannelNormalizer;

it('groups quality and backup variants under one normalized name', function (string $name) {
    expect(app(IptvChannelNormalizer::class)->normalize($name))->toBe('bein sports 1');
})->with([
    'fhd' => 'beIN Sports 1 FHD',
    'country and hd' => '[MA] beIN Sports 1 HD',
    'backup' => 'beIN Sports 1 Backup 2',
    'server' => 'beIN Sports 1 Server 1 HEVC',
]);

it('detects stream engines and quality labels', function () {
    $normalizer = app(IptvChannelNormalizer::class);

    expect($normalizer->streamType('https://example.com/live.m3u8'))->toBe('hls')
        ->and($normalizer->streamType('https://example.com/live.ts'))->toBe('mpegts')
        ->and($normalizer->quality('Arena 4K'))->toBe('4K')
        ->and($normalizer->quality('Arena FHD'))->toBe('FHD')
        ->and($normalizer->quality('Arena SD'))->toBe('SD');
});

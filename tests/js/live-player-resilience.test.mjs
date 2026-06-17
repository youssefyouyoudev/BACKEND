import assert from 'node:assert/strict';
import test from 'node:test';

import {
    detectStreamType,
    getBufferedAhead,
    toAbsoluteUrl,
} from '../../resources/js/stream-detection.js';

globalThis.window = {
    location: {
        origin: 'http://localhost:8000',
        hostname: 'localhost',
        protocol: 'http:',
    },
};
globalThis.document = {
    documentElement: {
        lang: 'en',
    },
};

test('detects explicit and URL-based stream types without crossing engines', () => {
    assert.equal(detectStreamType('https://example.test/live/channel.ts', 'hls'), 'hls');
    assert.equal(detectStreamType('https://example.test/channel.m3u8?token=abc'), 'hls');
    assert.equal(detectStreamType('https://example.test/live/user/pass/1234'), 'mpegts');
    assert.equal(detectStreamType('https://example.test/channel.ts?token=abc'), 'mpegts');
    assert.equal(detectStreamType('https://example.test/video.mp4'), 'mp4');
    assert.equal(detectStreamType('/play/iptv-source/42?signature=abc', 'auto'), 'auto');
});

test('reports only the buffer range containing the current playback position', () => {
    const video = {
        currentTime: 12,
        buffered: {
            length: 2,
            start: (index) => [0, 10][index],
            end: (index) => [5, 19.5][index],
        },
    };

    assert.equal(getBufferedAhead(video), 7.5);
});

test('returns zero when no buffered range contains the current position', () => {
    const video = {
        currentTime: 8,
        buffered: {
            length: 1,
            start: () => 0,
            end: () => 5,
        },
    };

    assert.equal(getBufferedAhead(video), 0);
});

test('converts protected stream paths to absolute worker-safe URLs', () => {
    assert.equal(
        toAbsoluteUrl('/play/iptv/8053?expires=1&signature=abc'),
        'http://localhost:8000/play/iptv/8053?expires=1&signature=abc',
    );
    assert.equal(
        toAbsoluteUrl('https://rifitv.com/play/iptv/8053?signature=abc'),
        'https://rifitv.com/play/iptv/8053?signature=abc',
    );
});

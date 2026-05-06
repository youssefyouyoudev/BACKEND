import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const players = [...document.querySelectorAll('[data-video-player]')];

    if (players.length === 0) {
        return;
    }

    Promise.all([
        import('video.js'),
        import('video.js/dist/video-js.css'),
    ]).then(([videojsModule]) => {
        const videojs = videojsModule.default;

        players.forEach((element) => {
            const configuredType = element.dataset.playerType ?? 'hls';
            const sourceElement = element.querySelector('source');
            const sourceUrl = sourceElement?.getAttribute('src') ?? '';
            const sourceType = sourceElement?.getAttribute('type') ?? inferMimeType(configuredType, sourceUrl);
            const errorPanel = element.parentElement?.querySelector('[data-player-error]');

            const player = videojs(element, {
                autoplay: true,
                controls: true,
                preload: 'auto',
                fluid: true,
                responsive: true,
                liveui: configuredType === 'hls',
                controlBar: {
                    pictureInPictureToggle: true,
                },
            });

            if (!looksLikePlayableStream(configuredType, sourceUrl)) {
                showPlayerError(
                    player,
                    errorPanel,
                    'This channel does not expose a browser-playable HLS, DASH, or MP4 source.'
                );
                return;
            }

            if (sourceUrl !== '') {
                player.src({
                    src: sourceUrl,
                    type: sourceType,
                });
            }

            player.ready(() => {
                player.play().catch(() => {
                    // Ignore autoplay restrictions and let the user start playback manually.
                });
            });

            player.on('error', () => {
                const code = player.error()?.code ?? 0;
                const message = resolveVideoErrorMessage(code, configuredType);
                showPlayerError(player, errorPanel, message);
            });
        });
    });
});

function inferMimeType(streamType, sourceUrl) {
    if (streamType === 'mp4' || sourceUrl.endsWith('.mp4')) {
        return 'video/mp4';
    }

    if (streamType === 'dash' || sourceUrl.endsWith('.mpd')) {
        return 'application/dash+xml';
    }

    return 'application/x-mpegURL';
}

function looksLikePlayableStream(streamType, sourceUrl) {
    if (streamType === 'hls' || streamType === 'mp4' || streamType === 'dash') {
        return true;
    }

    const normalized = sourceUrl.toLowerCase();

    return normalized.includes('.m3u8')
        || normalized.endsWith('/m3u8')
        || normalized.includes('.mp4')
        || normalized.includes('.mpd');
}

function resolveVideoErrorMessage(code, streamType) {
    if (code === 3) {
        return streamType === 'hls'
            ? 'This HLS playlist responded, but its media segments were unavailable or unsupported.'
            : 'Playback could not continue because the source playlist or media data was invalid.';
    }

    if (code === 4) {
        return 'The browser could not load this source. The channel may be offline or blocked upstream.';
    }

    if (code === 2) {
        return 'Network playback failed while loading this stream. Please try another channel.';
    }

    return 'This stream appears to be offline, invalid, or incompatible with browser playback right now.';
}

function showPlayerError(player, errorPanel, message) {
    if (errorPanel) {
        errorPanel.hidden = false;
        const messageNode = errorPanel.querySelector('[data-player-error-message]');

        if (messageNode) {
            messageNode.textContent = message;
        }
    }

    player.addClass('has-player-error');
}

export function initIptvPlayer() {
    document.querySelectorAll('[data-iptv-player]').forEach((video) => {
        if (video.dataset.playerReady) return;
        video.dataset.playerReady = '1';

        const streamUrl = video.dataset.streamUrl;
        const state = document.querySelector('[data-player-state]');
        const retry = document.querySelector('[data-player-retry]');
        let hls = null;

        const showState = (message, canRetry = false) => {
            if (!state) return;
            state.hidden = false;
            state.querySelector('p').textContent = message;
            retry.hidden = !canRetry;
        };

        const hideState = () => {
            if (state) state.hidden = true;
        };

        const load = () => {
            if (!streamUrl) {
                showState('This item does not have a playable stream URL.', false);
                return;
            }

            hls?.destroy();
            hls = null;
            showState('Loading stream...');

            const path = new URL(streamUrl, window.location.href).pathname.toLowerCase();
            const shouldUseHls = path.endsWith('.m3u8') && window.Hls?.isSupported();

            if (shouldUseHls) {
                hls = new window.Hls();
                hls.loadSource(streamUrl);
                hls.attachMedia(video);
                hls.on(window.Hls.Events.MANIFEST_PARSED, () => {
                    hideState();
                    video.play().catch(() => {});
                });
                hls.on(window.Hls.Events.ERROR, (_, data) => {
                    if (data.fatal) showState('The stream could not be played.', true);
                });
                return;
            }

            video.src = streamUrl;
            video.load();
            video.play().catch(() => {});
        };

        retry?.addEventListener('click', load);
        video.addEventListener('canplay', hideState);
        video.addEventListener('error', () => showState('The stream could not be played.', true));
        video.addEventListener('timeupdate', () => {
            if (!video.dataset.historyUrl || Math.floor(video.currentTime) % 15 !== 0) return;

            navigator.sendBeacon?.(video.dataset.historyUrl, new FormData());
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'f' || event.key === 'F') video.requestFullscreen?.();
            if (event.key === 'm' || event.key === 'M') video.muted = !video.muted;
            if (event.key === 'Escape' && document.fullscreenElement) document.exitFullscreen?.();
        });

        load();
    });
}

import { t } from '../i18n';

export function initIptvPlayer() {
    document.querySelectorAll('[data-iptv-player]').forEach((video) => {
        if (video.dataset.playerReady) return;
        video.dataset.playerReady = '1';

        const streamUrl = video.dataset.streamUrl;
        const state = document.querySelector('[data-player-state]');
        const retry = document.querySelector('[data-player-retry]');
        let hls = null;
        let mpegtsPlayer = null;

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
                showState(t('This item does not have a playable stream URL.'), false);
                return;
            }

            hls?.destroy();
            hls = null;
            if (mpegtsPlayer) {
                mpegtsPlayer.unload();
                mpegtsPlayer.detachMediaElement();
                mpegtsPlayer.destroy();
                mpegtsPlayer = null;
            }
            showState(t('Loading stream...'));

            const path = new URL(streamUrl, window.location.href).pathname.toLowerCase();
            const streamType = String(video.dataset.streamType || '').toLowerCase();
            const shouldUseHls = (streamType === 'hls' || streamType === 'm3u8' || path.endsWith('.m3u8'))
                && window.Hls?.isSupported();
            const shouldUseMpegTs = (
                ['mpegts', 'ts', 'stream'].includes(streamType)
                || path.endsWith('.ts')
                || (! streamType && ! path.endsWith('.m3u8') && ! path.endsWith('.mp4'))
            ) && window.mpegts?.isSupported();

            if (shouldUseHls) {
                hls = new window.Hls({
                    enableWorker: true,
                    manifestLoadingMaxRetry: 4,
                    levelLoadingMaxRetry: 4,
                    fragLoadingMaxRetry: 6,
                });
                hls.loadSource(streamUrl);
                hls.attachMedia(video);
                hls.on(window.Hls.Events.MANIFEST_PARSED, () => {
                    hideState();
                    video.play().catch(() => {});
                });
                hls.on(window.Hls.Events.ERROR, (_, data) => {
                    console.error('[RifiPlayer] HLS playback error.', {
                        type: data.type,
                        details: data.details,
                        fatal: data.fatal,
                        responseCode: data.response?.code,
                    });
                    if (!data.fatal) return;

                    if (data.type === window.Hls.ErrorTypes.NETWORK_ERROR) {
                        hls.startLoad();
                        return;
                    }

                    if (data.type === window.Hls.ErrorTypes.MEDIA_ERROR) {
                        hls.recoverMediaError();
                        return;
                    }

                    hls.destroy();
                    hls = null;
                    showState(t('The HLS stream could not be played in this browser.'), true);
                });
                return;
            }

            if (
                (streamType === 'hls' || streamType === 'm3u8' || path.endsWith('.m3u8'))
                && video.canPlayType('application/vnd.apple.mpegurl')
            ) {
                video.src = streamUrl;
                video.load();
                video.play().catch((error) => {
                    console.error('[RifiPlayer] Native HLS playback failed.', error);
                    showState(t('The HLS stream could not be played in this browser.'), true);
                });
                return;
            }

            if (shouldUseMpegTs) {
                mpegtsPlayer = window.mpegts.createPlayer({
                    type: 'mpegts',
                    isLive: true,
                    url: streamUrl,
                    cors: true,
                    withCredentials: false,
                }, {
                    enableWorker: false,
                    lazyLoad: false,
                    autoCleanupSourceBuffer: true,
                    autoCleanupMaxBackwardDuration: 60,
                    autoCleanupMinBackwardDuration: 30,
                });
                mpegtsPlayer.attachMediaElement(video);
                mpegtsPlayer.on(window.mpegts.Events.ERROR, () => {
                    showState(t('The MPEG-TS stream could not be played in this browser.'), true);
                });
                video.addEventListener('loadedmetadata', () => {
                    hideState();
                    video.play().catch(() => {});
                }, { once: true });
                mpegtsPlayer.load();
                return;
            }

            video.src = streamUrl;
            video.load();
            video.play().catch(() => {});
        };

        retry?.addEventListener('click', load);
        video.addEventListener('canplay', hideState);
        video.addEventListener('error', () => showState(t('The stream could not be played.'), true));
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

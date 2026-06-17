import { initResilientPlayer } from '../live-player-resilience';
import { t } from '../i18n';

const VOLUME_KEY = 'rifitv-player-volume-v1';

export function initIptvPlayer() {
    document.querySelectorAll('[data-iptv-player]').forEach((video) => {
        if (video.dataset.playerReady) return;
        video.dataset.playerReady = '1';

        const root = video.closest('[data-iptv-player-page]') || document;
        const state = root.querySelector('[data-player-state]');
        const stateTitle = root.querySelector('[data-player-state-title]');
        const retry = root.querySelector('[data-player-retry]');
        const backup = root.querySelector('[data-player-backup]');
        const picker = root.querySelector('[data-iptv-source-picker]');
        const debugPanel = root.querySelector('[data-player-debug]');
        let sources = [];
        let controller = null;
        let activeIndex = 0;

        try {
            sources = JSON.parse(video.dataset.playerSources || '[]');
        } catch {
            sources = [];
        }

        if (!sources.length && video.dataset.streamUrl) {
            sources = [{
                url: video.dataset.streamUrl,
                type: video.dataset.streamType || 'auto',
                label: t('Primary'),
            }];
        }

        const showState = (message, canRetry = false, title = '') => {
            if (!state) return;
            state.hidden = false;
            if (stateTitle) {
                stateTitle.hidden = !title;
                stateTitle.textContent = title;
            }
            state.querySelector('p').textContent = message;
            if (retry) retry.hidden = !canRetry;
            if (backup) backup.hidden = !sources[activeIndex + 1];
        };

        const hideState = () => {
            if (state) state.hidden = true;
        };

        const syncPicker = () => {
            picker?.querySelectorAll('[data-source-index]').forEach((button) => {
                button.classList.toggle('is-active', Number(button.dataset.sourceIndex) === activeIndex);
            });
        };

        const load = (index = activeIndex) => {
            const source = sources[index];
            if (!source?.url) {
                showState(t('This item does not have a playable stream URL.'));
                return;
            }

            activeIndex = index;
            syncPicker();
            controller?.destroy();
            showState(t('Loading stream...'));

            controller = initResilientPlayer(video, source.url, {
                streamType: source.type,
                sourceId: source.id,
                channelId: video.dataset.channelId,
                maxReconnects: 3,
                onLoading: () => showState(t('Loading stream...')),
                onCanPlay: hideState,
                onPlaying: hideState,
                onAutoplayBlocked: (message) => showState(message || t('Tap to start playback'), true),
                onDebug: (details) => {
                    if (debugPanel) debugPanel.textContent = JSON.stringify(details, null, 2);
                },
                onMixedContent: (message) => showState(message, true, t('Channel temporarily unavailable')),
                onReconnecting: (message) => showState(message),
                onForbidden: () => showState(t('This protected stream link has expired. Reload the page.'), true),
                onFatal: () => {
                    const next = activeIndex + 1;
                    if (sources[next]) {
                        showState(t('Source unstable, switching to backup...'));
                        window.setTimeout(() => load(next), 500);
                        return;
                    }
                    showState(
                        t('We tried reconnecting and switching sources. Try again, choose another source, or open another channel.'),
                        true,
                        t('Channel temporarily unavailable')
                    );
                },
            });
        };

        const storedVolume = Number(localStorage.getItem(VOLUME_KEY));
        if (Number.isFinite(storedVolume) && storedVolume >= 0 && storedVolume <= 1) {
            video.volume = storedVolume;
        }
        video.addEventListener('volumechange', () => localStorage.setItem(VOLUME_KEY, String(video.volume)));
        retry?.addEventListener('click', () => load(activeIndex));
        backup?.addEventListener('click', () => {
            if (sources[activeIndex + 1]) load(activeIndex + 1);
        });
        picker?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-source-index]');
            if (button) load(Number(button.dataset.sourceIndex));
        });

        document.addEventListener('keydown', (event) => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
            if (event.code === 'Space') {
                event.preventDefault();
                video.paused ? video.play().catch(() => {}) : video.pause();
            }
            if (event.key.toLowerCase() === 'f') video.closest('.iptv-video-shell')?.requestFullscreen?.();
            if (event.key.toLowerCase() === 'm') video.muted = !video.muted;
            if (event.key === 'ArrowUp') video.volume = Math.min(1, video.volume + 0.05);
            if (event.key === 'ArrowDown') video.volume = Math.max(0, video.volume - 0.05);
        });

        window.addEventListener('pagehide', () => controller?.destroy(), { once: true });
        load();
    });
}

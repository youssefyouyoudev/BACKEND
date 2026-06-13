import { initResilientPlayer } from './live-player-resilience';
import { t } from './i18n';

const storageKey = (matchId) => `rifitv-match-player:${matchId || 'unknown'}`;
const normalize = (value) => String(value || '').trim().toLocaleLowerCase();

const streamType = (source) => {
    const type = normalize(source?.type);
    const url = normalize(source?.url);

    if (['iframe', 'external'].includes(type)) return type;
    if (['hls', 'm3u', 'm3u8'].includes(type) || url.includes('.m3u8')) return 'hls';
    if (['mpegts', 'ts', 'stream', 'channel_proxy'].includes(type)) return 'mpegts';
    if (type === 'mp4' || url.includes('.mp4')) return 'mp4';

    return type || 'mpegts';
};

const statusLabel = (status) => ({
    active: t('Active'),
    loading: t('Loading'),
    failed: t('Failed'),
    online: t('Ready'),
    offline: t('Offline'),
    unknown: t('Ready'),
}[status] || t('Ready'));

class MatchPlayer {
    constructor(root) {
        this.root = root;
        this.config = JSON.parse(root.querySelector('[data-match-player-config]')?.textContent || '{}');
        this.sources = Array.isArray(this.config.sources)
            ? this.config.sources.map((source, index) => ({
                ...source,
                id: source.id ?? index,
                channel: source.channel || this.config.title || source.title || t('Live channel'),
                label: source.label || `${t('Server')} ${index + 1}`,
                url: source.browser_url || source.url,
                external_url: source.external_url || source.url,
            }))
            : [];
        this.video = root.querySelector('video');
        this.iframe = root.querySelector('[data-match-player-iframe]');
        this.overlay = root.querySelector('[data-match-player-overlay]');
        this.stateTitle = root.querySelector('[data-match-player-state-title]');
        this.stateMessage = root.querySelector('[data-match-player-state-message]');
        this.channelName = root.querySelector('[data-match-player-channel]');
        this.meta = root.querySelector('[data-match-player-meta]');
        this.health = root.querySelector('[data-match-player-health]');
        this.channels = root.querySelector('[data-match-player-channels]');
        this.servers = root.querySelector('[data-match-player-servers]');
        this.serverHeading = root.querySelector('[data-match-player-server-heading]');
        this.external = root.querySelector('[data-match-player-external]');
        this.playButton = root.querySelector('[data-match-player-play]');
        this.controller = null;
        this.activeIndex = this.initialIndex();
        this.failed = new Set();
        this.loading = new Set();
        this.bindControls();
    }

    initialIndex() {
        const saved = localStorage.getItem(storageKey(this.config.matchId));
        const savedIndex = this.sources.findIndex((source) => String(source.id) === saved);

        if (savedIndex >= 0) return savedIndex;

        const recommended = this.sources.findIndex((source) => source.recommended);
        return recommended >= 0 ? recommended : 0;
    }

    activeSource() {
        return this.sources[this.activeIndex] || null;
    }

    channelGroups() {
        return this.sources.reduce((groups, source, index) => {
            const name = source.channel || source.title || t('Channel');
            const key = normalize(name);
            const group = groups.get(key) || { key, name, indexes: [] };
            group.indexes.push(index);
            groups.set(key, group);
            return groups;
        }, new Map());
    }

    init() {
        if (!this.sources.length) {
            this.showError(t('Watch links will appear here before kickoff.'));
            return;
        }

        this.renderOptions();
        this.load(this.activeIndex);
    }

    load(index) {
        const source = this.sources[index];
        if (!source) return;

        this.controller?.destroy();
        this.controller = null;
        this.iframe.src = 'about:blank';
        this.iframe.hidden = true;
        this.video.hidden = false;
        this.activeIndex = index;
        this.failed.delete(index);
        this.loading.add(index);
        localStorage.setItem(storageKey(this.config.matchId), String(source.id));
        this.updateIdentity(source);
        this.renderOptions();
        this.showLoading(t('Preparing stream...'), t('Building a stable buffer for smoother playback.'));

        const type = streamType(source);
        if (type === 'external') {
            this.video.hidden = true;
            this.external.href = source.external_url || source.url;
            this.external.hidden = false;
            this.showError(t('This stream opens in an external player.'));
            return;
        }

        if (type === 'iframe') {
            this.video.hidden = true;
            this.iframe.hidden = false;
            this.iframe.src = source.url;
            this.external.href = source.external_url || source.url;
            this.external.hidden = false;
            this.loading.delete(index);
            this.hideOverlay();
            this.renderOptions();
            return;
        }

        if (type === 'other' || !source.url) {
            this.video.hidden = true;
            this.external.href = source.external_url || source.url || '#';
            this.external.hidden = !source.url;
            this.loading.delete(index);
            this.showError(t('This stream type is not supported by the browser player.'));
            this.renderOptions();
            return;
        }

        this.external.hidden = true;
        this.controller = initResilientPlayer(this.video, source.url, {
            streamType: type,
            autoplay: this.config.autoplay,
            onLoading: () => this.showLoading(t('Preparing stream...'), t('Connecting to :server', {
                server: source.label || t('Server'),
            })),
            onCanPlay: () => this.showLoading(t('Stream ready'), t('Press play if playback does not start automatically.')),
            onPlaying: () => {
                this.loading.delete(index);
                this.playButton.hidden = true;
                this.hideOverlay();
                this.renderOptions();
            },
            onAutoplayBlocked: () => {
                this.playButton.hidden = false;
                this.hideOverlay();
            },
            onReconnecting: (message) => this.showLoading(t('Reconnecting...'), message),
            onForbidden: () => this.showError(t('This protected stream link has expired. Refresh the match page.')),
            onFatal: (message) => this.failover(message),
        });
    }

    failover(message) {
        this.failed.add(this.activeIndex);
        this.loading.delete(this.activeIndex);
        const next = this.nextIndex();

        if (next !== null) {
            const nextSource = this.sources[next];
            this.showLoading(t('Trying next server'), `${nextSource.channel} - ${nextSource.label}`);
            window.setTimeout(() => this.load(next), 700);
            return;
        }

        this.showError(message || t('No available server could be started. Choose another channel or try again.'));
        this.renderOptions();
    }

    nextIndex() {
        const source = this.activeSource();
        const sameChannel = this.sources.findIndex((candidate, index) => (
            index !== this.activeIndex
            && !this.failed.has(index)
            && normalize(candidate.channel) === normalize(source?.channel)
        ));
        if (sameChannel >= 0) return sameChannel;

        const any = this.sources.findIndex((_, index) => index !== this.activeIndex && !this.failed.has(index));
        return any >= 0 ? any : null;
    }

    updateIdentity(source) {
        this.channelName.textContent = source.channel || source.title || t('Live channel');
        this.meta.textContent = [
            source.label,
            source.quality,
            source.language,
            source.commentator,
        ].filter(Boolean).join(' · ');
        this.health.textContent = statusLabel(source.health_status || 'unknown');
        this.serverHeading.textContent = source.channel || t('Select a server');
    }

    renderOptions() {
        const active = this.activeSource();
        const activeChannel = normalize(active?.channel);
        const groups = [...this.channelGroups().values()];

        this.channels.replaceChildren(...groups.map((group) => {
            const firstIndex = group.indexes.find((index) => !this.failed.has(index)) ?? group.indexes[0];
            const source = this.sources[firstIndex];
            const button = document.createElement('button');
            button.type = 'button';
            button.role = 'tab';
            button.className = 'rifitv-channel-tab';
            button.classList.toggle('is-active', group.key === activeChannel);
            button.setAttribute('aria-selected', String(group.key === activeChannel));
            button.innerHTML = `<strong>${this.escape(group.name)}</strong><small>${group.indexes.length} ${group.indexes.length === 1 ? t('server') : t('servers')}</small>`;
            if (source?.recommended) button.insertAdjacentHTML('beforeend', `<span>${this.escape(t('Recommended'))}</span>`);
            button.addEventListener('click', () => this.load(firstIndex));
            return button;
        }));

        const indexes = groups.find((group) => group.key === activeChannel)?.indexes || [];
        this.servers.replaceChildren(...indexes.map((index, position) => {
            const source = this.sources[index];
            const isActive = index === this.activeIndex;
            const state = this.failed.has(index) ? 'failed' : (this.loading.has(index) ? 'loading' : (isActive ? 'active' : source.health_status || 'unknown'));
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `rifitv-server-card is-${state}`;
            button.setAttribute('aria-pressed', String(isActive));
            button.innerHTML = `
                <span class="rifitv-server-card__number">${position + 1}</span>
                <span><strong>${this.escape(source.label || `${t('Server')} ${position + 1}`)}</strong><small>${this.escape([source.quality, source.language].filter(Boolean).join(' · ') || streamType(source).toUpperCase())}</small></span>
                <b>${this.escape(statusLabel(state))}</b>
            `;
            button.addEventListener('click', () => this.load(index));
            return button;
        }));
    }

    bindControls() {
        this.root.querySelector('[data-match-player-reload]')?.addEventListener('click', () => this.load(this.activeIndex));
        this.root.querySelector('[data-match-player-next]')?.addEventListener('click', () => {
            const next = this.nextIndex();
            if (next !== null) {
                this.load(next);
                return;
            }

            window.dispatchEvent(new CustomEvent('rifi:player-next'));
        });
        this.root.querySelector('[data-match-player-mute]')?.addEventListener('click', () => {
            this.video.muted = !this.video.muted;
        });
        this.playButton?.addEventListener('click', () => {
            this.video.play().then(() => {
                this.playButton.hidden = true;
            }).catch(() => this.showError(t('Playback needs another tap.')));
        });
        this.root.querySelector('[data-match-player-fullscreen]')?.addEventListener('click', () => {
            this.root.querySelector('.rifitv-player-shell')?.requestFullscreen?.();
        });
        window.addEventListener('pagehide', () => this.controller?.destroy(), { once: true });
    }

    showLoading(title, message) {
        this.overlay.hidden = false;
        this.overlay.classList.remove('is-error');
        this.stateTitle.textContent = title;
        this.stateMessage.textContent = message;
    }

    showError(message) {
        this.overlay.hidden = false;
        this.overlay.classList.add('is-error');
        this.stateTitle.textContent = t('Stream unavailable');
        this.stateMessage.textContent = message;
    }

    hideOverlay() {
        this.overlay.hidden = true;
    }

    escape(value) {
        const span = document.createElement('span');
        span.textContent = String(value ?? '');
        return span.innerHTML;
    }
}

export function initMatchPlayers() {
    document.querySelectorAll('[data-match-player]').forEach((root) => {
        if (root.dataset.matchPlayerReady) return;
        root.dataset.matchPlayerReady = '1';
        new MatchPlayer(root).init();
    });
}

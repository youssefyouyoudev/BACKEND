@props([
    'channel' => null,
    'sources' => [],
    'poster' => null,
    'autoplay' => true,
])

@php
    $playerId = 'rifi-player-'.uniqid();
    $payload = [
        'channel' => [
            'id' => data_get($channel, 'id'),
            'name' => data_get($channel, 'name', 'Live Channel'),
        ],
        'sources' => collect($sources)->values()->all(),
        'poster' => $poster,
        'autoplay' => $autoplay,
    ];
@endphp

<div class="sat-player rm-player-frame" data-rifi-video-player data-config='@json($payload)' data-player-id="{{ $playerId }}">
    <video
        id="{{ $playerId }}"
        class="video-js vjs-big-play-centered sat-player__video rm-player-video"
        controls
        playsinline
        preload="auto"
        @if($poster) poster="{{ $poster }}" @endif
    ></video>

    <div class="sat-player__loading rm-player-loading" data-player-loading hidden>
        <span></span>
        <strong>Connecting to broadcast</strong>
    </div>

    <div class="sat-player__error rm-player-error" data-player-error hidden>
        <span>Stream unavailable</span>
        <h3 data-player-error-title>Channel unavailable</h3>
        <p data-player-error-message>This broadcast did not respond in time. Try another channel or retry the stream.</p>
        <div class="sat-player__error-actions rm-player-error__actions">
            <button type="button" class="rm-btn rm-btn-primary" data-player-retry>Retry</button>
            <button type="button" class="rm-btn rm-btn-secondary" data-player-next>Next channel</button>
        </div>
    </div>

    <div class="sat-player__topline rm-player-topline">
        <span class="sat-live-dot rm-live-dot"></span>
        <strong data-player-title>{{ data_get($channel, 'name', 'Live Channel') }}</strong>
        <select data-player-quality aria-label="Playback quality" hidden>
            <option value="-1">Auto</option>
        </select>
    </div>

    <div class="rm-server-selector" data-player-servers aria-label="Stream servers"></div>
</div>

@once
    @push('scripts')
        <script>
        (() => {
            const STARTUP_TIMEOUT = 25000;
            const MAX_RETRIES = 1;

            const waitForLibraries = () => new Promise((resolve, reject) => {
                const started = Date.now();
                const timer = setInterval(() => {
                    if (window.videojs && window.Hls) {
                        clearInterval(timer);
                        resolve();
                        return;
                    }

                    if (Date.now() - started > 8000) {
                        clearInterval(timer);
                        reject(new Error('Video.js or HLS.js did not load.'));
                    }
                }, 80);
            });

            const serverLabel = (source, index) => source?.label || `Server ${index + 1}`;
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const streamTypeFor = (source) => {
                const type = String(source?.type || '').toLowerCase();
                const url = String(source?.url || '').toLowerCase();

                if (type === 'mpegts' || type === 'ts' || url.includes('.ts')) return 'mpegts';
                if (type === 'mp4' || url.includes('.mp4')) return 'mp4';
                if (type === 'dash' || url.includes('.mpd')) return 'dash';
                if (type === 'hls' || url.includes('.m3u8') || url.includes('.m3u')) return 'hls';

                return 'stream';
            };

            const mimeTypeFor = (type) => {
                if (type === 'mp4') return 'video/mp4';
                if (type === 'dash') return 'application/dash+xml';
                if (type === 'mpegts') return 'video/mp2t';

                return 'application/x-mpegURL';
            };

            const firstPlayableFromM3u = async (url) => {
                const response = await fetch(url, { headers: { Accept: 'application/x-mpegURL, text/plain, */*' } });
                if (!response.ok) throw new Error(`Playlist request failed with HTTP ${response.status}`);
                const text = await response.text();

                if (text.trim().startsWith('#EXTM3U')) {
                    const line = text.split(/\r?\n/)
                        .map((entry) => entry.trim())
                        .find((entry) => entry !== '' && !entry.startsWith('#'));

                    return line || url;
                }

                return url;
            };

            class RifiVideoPlayer {
                constructor(root) {
                    this.root = root;
                    this.video = root.querySelector('video');
                    this.loading = root.querySelector('[data-player-loading]');
                    this.error = root.querySelector('[data-player-error]');
                    this.errorMessage = root.querySelector('[data-player-error-message]');
                    this.retryButton = root.querySelector('[data-player-retry]');
                    this.nextButton = root.querySelector('[data-player-next]');
                    this.quality = root.querySelector('[data-player-quality]');
                    this.serverSelector = root.querySelector('[data-player-servers]');
                    this.config = JSON.parse(root.dataset.config || '{}');
                    this.sources = this.config.sources || [];
                    this.activeIndex = 0;
                    this.retries = 0;
                    this.timeout = null;
                    this.hls = null;
                    this.mpegts = null;
                    this.player = null;
                    this.failedIndexes = new Set();

                    this.retryButton?.addEventListener('click', () => this.retry(true));
                    this.nextButton?.addEventListener('click', () => window.dispatchEvent(new CustomEvent('rifi:player-next')));
                }

                async init() {
                    await waitForLibraries();

                    this.player = videojs(this.video, {
                        autoplay: false,
                        controls: true,
                        fluid: true,
                        responsive: true,
                        liveui: true,
                        inactivityTimeout: 1800,
                        controlBar: {
                            pictureInPictureToggle: true,
                            fullscreenToggle: true,
                            volumePanel: { inline: false },
                        },
                    });

                    this.player.on('waiting', () => this.showLoading());
                    this.player.on('playing', () => this.showReady());
                    this.player.on('canplay', () => this.showReady());
                    this.player.on('error', () => {
                        const error = this.player.error();
                        console.error('[RiFiPlayer] Video.js error', {
                            error,
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            type: streamTypeFor(this.currentSource()),
                        });
                        this.handleFailure(error?.message || 'Video playback failed.');
                    });

                    this.renderServers();
                    await this.load(this.activeIndex);
                }

                currentSource() {
                    return this.sources[this.activeIndex] || this.sources[0] || null;
                }

                async load(index = 0) {
                    this.activeIndex = Math.max(0, Math.min(index, this.sources.length - 1));
                    this.retries = 0;
                    this.renderServers();
                    await this.playCurrent();
                }

                async retry(force = false) {
                    if (force) this.retries = 0;
                    await this.playCurrent();
                }

                async playCurrent() {
                    const source = this.currentSource();
                    const streamType = streamTypeFor(source);
                    const label = serverLabel(source, this.activeIndex);
                    console.log('[RiFiPlayer] Loading stream', {
                        server: label,
                        type: streamType,
                        engine: this.engineFor(streamType),
                        url: source?.url,
                    });

                    if (!source?.url) {
                        this.showError('Channel unavailable', 'No stream URL is configured for this channel.');
                        return;
                    }

                    this.teardownHls();
                    this.teardownMpegts();
                    this.resetNativeSource();
                    this.showLoading();
                    this.startTimeout();

                    try {
                        const sourceUrl = String(source.url).toLowerCase().includes('.m3u') && !String(source.url).toLowerCase().includes('.m3u8')
                            ? await firstPlayableFromM3u(source.url)
                            : source.url;

                        if (streamType === 'hls' && this.video.canPlayType('application/vnd.apple.mpegurl')) {
                            console.log('[RiFiPlayer] Using native HLS', { server: label });
                            this.player.src({ src: sourceUrl, type: 'application/vnd.apple.mpegurl' });
                            this.player.ready(() => this.safePlay());
                            return;
                        }

                        if (streamType === 'hls' && window.Hls?.isSupported()) {
                            console.log('[RiFiPlayer] Using hls.js', { server: label });
                            this.hls = new Hls({
                                enableWorker: true,
                                lowLatencyMode: true,
                                backBufferLength: 30,
                                manifestLoadingTimeOut: STARTUP_TIMEOUT,
                                fragLoadingTimeOut: STARTUP_TIMEOUT,
                            });

                            this.hls.on(Hls.Events.MANIFEST_PARSED, (_, data) => {
                                this.populateQuality(data.levels || []);
                                this.safePlay();
                            });

                            this.hls.on(Hls.Events.LEVEL_SWITCHED, (_, data) => {
                                if (this.quality && this.hls) this.quality.value = String(this.hls.currentLevel);
                            });

                            this.hls.on(Hls.Events.ERROR, (_, data) => {
                                console.error('[RiFiPlayer] HLS.js error', data, source);

                                if (!data.fatal) return;
                                if (data.type === Hls.ErrorTypes.NETWORK_ERROR && this.hls) {
                                    this.hls.startLoad();
                                    return;
                                }

                                this.handleFailure(data.details || 'Fatal HLS playback error.');
                            });

                            this.hls.loadSource(sourceUrl);
                            this.hls.attachMedia(this.video);
                            return;
                        }

                        if ((streamType === 'mpegts' || streamType === 'stream') && window.mpegts?.isSupported()) {
                            console.log('[RiFiPlayer] Using mpegts.js', { server: label });
                            this.mpegts = window.mpegts.createPlayer({
                                type: 'mpegts',
                                isLive: true,
                                url: sourceUrl,
                            }, {
                                enableWorker: true,
                                lazyLoad: false,
                                liveBufferLatencyChasing: true,
                            });
                            this.mpegts.attachMediaElement(this.video);
                            this.mpegts.on(window.mpegts.Events.ERROR, (type, detail, info) => {
                                console.error('[RiFiPlayer] mpegts.js error', { type, detail, info, server: label });
                                this.handleFailure(detail || type || 'MPEG-TS playback failed.');
                            });
                            this.video.addEventListener('loadedmetadata', () => this.safePlay(), { once: true });
                            this.mpegts.load();
                            return;
                        }

                        console.log('[RiFiPlayer] Using native video', { server: label, type: streamType });
                        this.player.src({ src: sourceUrl, type: mimeTypeFor(streamType) });
                        this.player.ready(() => this.safePlay());
                    } catch (error) {
                        console.error('[RiFiPlayer] Stream preparation failed', {
                            error,
                            server: label,
                            type: streamType,
                        });
                        this.handleFailure(error.message || 'Stream preparation failed.');
                    }
                }

                safePlay() {
                    this.clearTimeout();
                    this.player.play().then(() => this.showReady()).catch((error) => {
                        console.warn('[RiFiPlayer] Autoplay blocked or failed', error);
                        this.showReady();
                    });
                }

                handleFailure(message) {
                    this.clearTimeout();
                    this.failedIndexes.add(this.activeIndex);
                    this.renderServers();
                    const failedLabel = serverLabel(this.currentSource(), this.activeIndex);

                    if (this.retries < MAX_RETRIES) {
                        this.retries += 1;
                        console.warn('[RiFiPlayer] Retrying failed server', { server: failedLabel, retry: this.retries, reason: message });
                        setTimeout(() => this.playCurrent(), 900 * this.retries);
                        return;
                    }

                    const nextIndex = this.nextAvailableIndex();
                    if (nextIndex !== null) {
                        console.warn('[RiFiPlayer] Server failed, trying fallback', {
                            failed: failedLabel,
                            next: serverLabel(this.sources[nextIndex], nextIndex),
                            reason: message,
                        });
                        this.activeIndex = nextIndex;
                        this.retries = 0;
                        this.renderServers();
                        this.playCurrent();
                        return;
                    }

                    this.showError('Stream unavailable', `${failedLabel} failed. Try another server or come back later. ${message || ''}`.trim());
                }

                startTimeout() {
                    this.clearTimeout();
                    this.timeout = setTimeout(() => {
                        console.error('[RiFiPlayer] Startup timeout', {
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            type: streamTypeFor(this.currentSource()),
                            timeout_ms: STARTUP_TIMEOUT,
                        });
                        this.handleFailure(`The stream did not start within ${Math.round(STARTUP_TIMEOUT / 1000)} seconds.`);
                    }, STARTUP_TIMEOUT);
                }

                clearTimeout() {
                    if (this.timeout) {
                        clearTimeout(this.timeout);
                        this.timeout = null;
                    }
                }

                populateQuality(levels) {
                    if (!this.quality || !this.hls || !levels.length) return;

                    this.quality.innerHTML = '<option value="-1">Auto</option>' + levels
                        .map((level, index) => `<option value="${index}">${level.height ? `${level.height}p` : `${Math.round((level.bitrate || 0) / 1000)} kbps`}</option>`)
                        .join('');
                    this.quality.hidden = false;
                    this.quality.onchange = () => {
                        if (this.hls) this.hls.currentLevel = Number(this.quality.value);
                    };
                }

                showLoading() {
                    this.root.classList.add('is-loading');
                    this.root.classList.remove('has-error');
                    if (this.loading) this.loading.hidden = false;
                    if (this.error) this.error.hidden = true;
                }

                showReady() {
                    this.clearTimeout();
                    this.root.classList.remove('is-loading', 'has-error');
                    if (this.loading) this.loading.hidden = true;
                    if (this.error) this.error.hidden = true;
                }

                showError(title, message) {
                    this.teardownHls();
                    this.teardownMpegts();
                    this.root.classList.remove('is-loading');
                    this.root.classList.add('has-error');
                    if (this.loading) this.loading.hidden = true;
                    if (this.error) this.error.hidden = false;
                    const errorTitle = this.root.querySelector('[data-player-error-title]');
                    if (errorTitle) errorTitle.textContent = title;
                    if (this.errorMessage) this.errorMessage.textContent = message;
                }

                teardownHls() {
                    if (this.hls) {
                        this.hls.destroy();
                        this.hls = null;
                    }
                }

                teardownMpegts() {
                    if (this.mpegts) {
                        this.mpegts.unload();
                        this.mpegts.detachMediaElement();
                        this.mpegts.destroy();
                        this.mpegts = null;
                    }
                }

                resetNativeSource() {
                    if (this.player) {
                        this.player.pause();
                        this.player.src({ src: '', type: '' });
                    }
                    this.video.removeAttribute('src');
                    this.video.load();
                    if (this.quality) this.quality.hidden = true;
                }

                nextAvailableIndex() {
                    for (let index = this.activeIndex + 1; index < this.sources.length; index++) {
                        if (!this.failedIndexes.has(index)) return index;
                    }

                    return null;
                }

                engineFor(streamType) {
                    if (streamType === 'hls') return this.video.canPlayType('application/vnd.apple.mpegurl') ? 'native-hls' : 'hls.js';
                    if (streamType === 'mpegts' || streamType === 'stream') return window.mpegts?.isSupported() ? 'mpegts.js' : 'native';

                    return 'native';
                }

                renderServers() {
                    if (!this.serverSelector) return;

                    if (this.sources.length === 0) {
                        this.serverSelector.innerHTML = '';
                        return;
                    }

                    this.serverSelector.innerHTML = this.sources.map((source, index) => {
                        const label = serverLabel(source, index);
                        const health = source.health_status || 'unknown';
                        const active = index === this.activeIndex ? ' is-active' : '';
                        const failed = this.failedIndexes.has(index) ? ' is-failed' : '';

                        return `<button type="button" class="rm-server-option${active}${failed}" data-server-index="${index}">
                            <span>${escapeHtml(label)}</span>
                            <small>${escapeHtml(source.quality || streamTypeFor(source).toUpperCase())} · ${escapeHtml(health)}</small>
                        </button>`;
                    }).join('');

                    this.serverSelector.querySelectorAll('[data-server-index]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const index = Number(button.dataset.serverIndex);
                            this.failedIndexes.delete(index);
                            this.load(index);
                        });
                    });
                }
            }

            window.RifiVideoPlayers = window.RifiVideoPlayers || new Map();
            window.bootRifiVideoPlayers = () => {
                document.querySelectorAll('[data-rifi-video-player]').forEach((root) => {
                    if (window.RifiVideoPlayers.has(root)) return;

                    const instance = new RifiVideoPlayer(root);
                    window.RifiVideoPlayers.set(root, instance);
                    instance.init().catch((error) => {
                        console.error('[RiFiPlayer] Boot failed', error);
                        instance.showError('Player unavailable', error.message || 'Player libraries could not be loaded.');
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', window.bootRifiVideoPlayers);
            } else {
                window.bootRifiVideoPlayers();
            }
        })();
        </script>
    @endpush
@endonce

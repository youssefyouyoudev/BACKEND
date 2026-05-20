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
        class="sat-player__video rm-player-video"
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
            const STARTUP_TIMEOUT = 30000;
            const MAX_RETRIES = 1;

            const waitForLibraries = () => new Promise((resolve, reject) => {
                const started = Date.now();
                const timer = setInterval(() => {
                    if (window.Hls || window.mpegts || Date.now() - started > 1200) {
                        clearInterval(timer);
                        resolve();
                        return;
                    }

                    if (Date.now() - started > 8000) {
                        clearInterval(timer);
                        reject(new Error('Player libraries did not load.'));
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

            const detectStreamType = (source) => {
                const type = String(source?.type || '').toLowerCase();
                const url = String(source?.url || '').toLowerCase();

                if (['mpegts', 'ts', 'stream'].includes(type)) return 'mpegts';
                if (url.endsWith('.ts') || url.includes('.ts?') || url.endsWith('/ts') || url.includes('/ts?')) return 'mpegts';
                if (type === 'hls' || url.includes('.m3u8') || url.includes('.m3u')) return 'hls';
                if (type === 'mp4' || url.includes('.mp4')) return 'mp4';
                if (type === 'dash' || url.includes('.mpd')) return 'dash';

                return 'mpegts';
            };

            const mimeTypeFor = (type) => {
                if (type === 'mp4') return 'video/mp4';
                if (type === 'dash') return 'application/dash+xml';
                if (type === 'mpegts') return 'video/mp2t';

                return 'application/octet-stream';
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

                    this.bindNativeVideoEvents();
                    this.retryButton?.addEventListener('click', () => this.retry(true));
                    this.nextButton?.addEventListener('click', () => window.dispatchEvent(new CustomEvent('rifi:player-next')));
                }

                async init() {
                    await waitForLibraries();
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
                    await this.loadServer();
                }

                async retry(force = false) {
                    if (force) this.retries = 0;
                    await this.loadServer();
                }

                async loadServer() {
                    const source = this.currentSource();
                    const streamType = detectStreamType(source);
                    const label = serverLabel(source, this.activeIndex);

                    console.log('[RiFiPlayer] Loading stream', {
                        server: label,
                        type: streamType,
                        engine: this.engineFor(streamType),
                    });

                    if (!source?.url) {
                        this.showError('Channel unavailable', 'No stream URL is configured for this channel.');
                        return;
                    }

                    this.cleanupPlayer();
                    this.showLoading();
                    this.startTimeout();

                    try {
                        const sourceUrl = String(source.url).toLowerCase().includes('.m3u') && !String(source.url).toLowerCase().includes('.m3u8')
                            ? await firstPlayableFromM3u(source.url)
                            : source.url;

                        if (streamType === 'hls') {
                            this.loadWithHls(sourceUrl, label);
                            return;
                        }

                        if (streamType === 'mpegts') {
                            this.loadWithMpegTs(sourceUrl, label);
                            return;
                        }

                        this.loadWithNative(sourceUrl, streamType, label);
                    } catch (error) {
                        console.error('[RiFiPlayer] Stream preparation failed', {
                            error,
                            server: label,
                            type: streamType,
                        });
                        this.handleFailure(error.message || 'Stream preparation failed.');
                    }
                }

                createFreshVideoElement() {
                    const id = this.root.dataset.playerId || `rifi-player-${Date.now()}`;
                    const video = document.createElement('video');
                    video.id = id;
                    video.className = 'sat-player__video rm-player-video';
                    video.controls = true;
                    video.playsInline = true;
                    video.preload = 'auto';
                    video.setAttribute('playsinline', '');

                    if (this.config.poster) {
                        video.poster = this.config.poster;
                    }

                    const oldVideo = this.root.querySelector('video');
                    if (oldVideo) {
                        oldVideo.replaceWith(video);
                    } else {
                        this.root.insertBefore(video, this.root.firstElementChild);
                    }

                    this.video = video;
                    this.bindNativeVideoEvents();

                    return video;
                }

                bindNativeVideoEvents() {
                    if (!this.video) return;

                    const markReady = () => this.showReady();
                    this.video.addEventListener('loadedmetadata', markReady, { once: true });
                    this.video.addEventListener('canplay', markReady, { once: true });
                    this.video.addEventListener('playing', markReady, { once: true });
                    this.video.addEventListener('timeupdate', markReady, { once: true });
                    this.video.addEventListener('waiting', () => this.showLoading());
                    this.video.addEventListener('error', () => {
                        const error = this.video.error;
                        console.error('[RiFiPlayer] Native video error', {
                            code: error?.code,
                            message: error?.message,
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            type: detectStreamType(this.currentSource()),
                        });
                        this.handleFailure(error?.message || 'Video playback failed.');
                    });
                }

                loadWithMpegTs(streamUrl, label) {
                    this.createFreshVideoElement();

                    if (!window.mpegts?.isSupported()) {
                        this.handleFailure('MPEG-TS playback is not supported by this browser.');
                        return;
                    }

                    console.log('[RiFiPlayer] Using mpegts.js', { server: label });
                    this.mpegts = window.mpegts.createPlayer({
                        type: 'mpegts',
                        isLive: true,
                        url: streamUrl,
                        cors: true,
                        withCredentials: false,
                    }, {
                        enableWorker: true,
                        lazyLoad: false,
                        stashInitialSize: 128,
                        liveBufferLatencyChasing: true,
                    });

                    this.mpegts.on(window.mpegts.Events.ERROR, (type, detail, info) => {
                        console.error('[RiFiPlayer] mpegts.js error', { type, detail, info, server: label });
                        this.handleFailure(detail || type || 'MPEG-TS playback failed.');
                    });

                    this.mpegts.attachMediaElement(this.video);
                    this.mpegts.load();

                    const playPromise = typeof this.mpegts.play === 'function'
                        ? this.mpegts.play()
                        : this.video.play();

                    Promise.resolve(playPromise).catch((error) => {
                        console.warn('[RiFiPlayer] MPEG-TS autoplay blocked or failed', { error, server: label });
                    });
                }

                loadWithHls(streamUrl, label) {
                    this.createFreshVideoElement();

                    if (this.video.canPlayType('application/vnd.apple.mpegurl')) {
                        console.log('[RiFiPlayer] Using native HLS', { server: label });
                        this.video.src = streamUrl;
                        this.video.load();
                        this.safePlay();
                        return;
                    }

                    if (!window.Hls?.isSupported()) {
                        this.handleFailure('HLS playback is not supported by this browser.');
                        return;
                    }

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

                    this.hls.on(Hls.Events.LEVEL_SWITCHED, () => {
                        if (this.quality && this.hls) this.quality.value = String(this.hls.currentLevel);
                    });

                    this.hls.on(Hls.Events.ERROR, (_, data) => {
                        console.error('[RiFiPlayer] HLS.js error', { data, server: label });

                        if (!data.fatal) return;
                        if (data.type === Hls.ErrorTypes.NETWORK_ERROR && this.hls) {
                            this.hls.startLoad();
                            return;
                        }

                        this.handleFailure(data.details || 'Fatal HLS playback error.');
                    });

                    this.hls.loadSource(streamUrl);
                    this.hls.attachMedia(this.video);
                }

                loadWithNative(streamUrl, streamType, label) {
                    this.createFreshVideoElement();
                    console.log('[RiFiPlayer] Using native video', { server: label, type: streamType });
                    this.video.src = streamUrl;
                    this.video.type = mimeTypeFor(streamType);
                    this.video.load();
                    this.safePlay();
                }

                safePlay() {
                    if (!this.config.autoplay) {
                        this.showReady();
                        return;
                    }

                    this.video.play().then(() => this.showReady()).catch((error) => {
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
                        setTimeout(() => this.loadServer(), 900 * this.retries);
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
                        this.loadServer();
                        return;
                    }

                    this.showError('Stream unavailable', `${failedLabel} failed. Try another server or come back later. ${message || ''}`.trim());
                }

                startTimeout() {
                    this.clearTimeout();
                    this.timeout = setTimeout(() => {
                        console.error('[RiFiPlayer] Startup timeout', {
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            type: detectStreamType(this.currentSource()),
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
                    this.cleanupPlayer();
                    this.root.classList.remove('is-loading');
                    this.root.classList.add('has-error');
                    if (this.loading) this.loading.hidden = true;
                    if (this.error) this.error.hidden = false;
                    const errorTitle = this.root.querySelector('[data-player-error-title]');
                    if (errorTitle) errorTitle.textContent = title;
                    if (this.errorMessage) this.errorMessage.textContent = message;
                }

                cleanupPlayer() {
                    this.clearTimeout();
                    this.teardownHls();
                    this.teardownMpegts();
                    this.teardownVideoJs();
                    this.resetNativeSource();
                    if (this.quality) this.quality.hidden = true;
                }

                teardownVideoJs() {
                    if (!this.player) return;

                    try {
                        this.player.dispose();
                    } catch (error) {
                        console.warn('[RiFiPlayer] Video.js dispose failed', error);
                    }

                    this.player = null;
                }

                teardownHls() {
                    if (this.hls) {
                        this.hls.destroy();
                        this.hls = null;
                    }
                }

                teardownMpegts() {
                    if (!this.mpegts) return;

                    try {
                        this.mpegts.unload();
                        this.mpegts.detachMediaElement();
                        this.mpegts.destroy();
                    } catch (error) {
                        console.warn('[RiFiPlayer] mpegts.js destroy failed', error);
                    }

                    this.mpegts = null;
                }

                resetNativeSource() {
                    if (!this.video) return;

                    try {
                        this.video.pause();
                        this.video.removeAttribute('src');
                        this.video.load();
                    } catch (error) {
                        console.warn('[RiFiPlayer] Native video reset failed', error);
                    }
                }

                nextAvailableIndex() {
                    for (let index = this.activeIndex + 1; index < this.sources.length; index++) {
                        if (!this.failedIndexes.has(index)) return index;
                    }

                    return null;
                }

                engineFor(streamType) {
                    if (streamType === 'hls') return this.video?.canPlayType('application/vnd.apple.mpegurl') ? 'native-hls' : 'hls.js';
                    if (streamType === 'mpegts') return window.mpegts?.isSupported() ? 'mpegts.js' : 'unsupported';

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
                            <small>${escapeHtml(source.quality || detectStreamType(source).toUpperCase())} - ${escapeHtml(health)}</small>
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

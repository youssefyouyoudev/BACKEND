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

<div class="sat-player rm-player-frame rifi-player-stage" data-rifi-video-player data-config='@json($payload)' data-player-id="{{ $playerId }}">
    <video
        id="{{ $playerId }}"
        class="sat-player__video rm-player-video rifi-video-element"
        controls
        playsinline
        preload="auto"
        @if($poster) poster="{{ $poster }}" @endif
    ></video>

    <div class="sat-player__loading rm-player-loading rifi-player-overlay" data-player-loading hidden>
        <span class="rifi-player-spinner"></span>
        <strong class="rifi-player-status-title" data-player-status-title>Connecting to broadcast</strong>
        <p class="rifi-player-status-subtitle" data-player-status-subtitle>Preparing the live signal.</p>
    </div>

    <div class="sat-player__error rm-player-error rifi-player-overlay rifi-player-overlay--error" data-player-error hidden>
        <span>Stream unavailable</span>
        <h3 data-player-error-title>Channel unavailable</h3>
        <p data-player-error-message>This broadcast did not respond in time. Try another channel or retry the stream.</p>
        <div class="sat-player__error-actions rm-player-error__actions rifi-player-error-actions">
            <button type="button" class="rm-btn rm-btn-primary" data-player-retry>Retry</button>
            <button type="button" class="rm-btn rm-btn-secondary" data-player-next>Next channel</button>
            <a href="#" class="rm-btn rm-btn-secondary" data-player-external target="_blank" rel="noopener">Open external player</a>
            <a href="{{ route('home') }}" class="rm-btn rm-btn-secondary">Back to channels</a>
        </div>
    </div>

    <div class="sat-player__topline rm-player-topline">
        <span class="sat-live-dot rm-live-dot"></span>
        <strong data-player-title>{{ data_get($channel, 'name', 'Live Channel') }}</strong>
        <select data-player-quality aria-label="Playback quality" hidden>
            <option value="-1">Auto</option>
        </select>
    </div>

    <div class="rifi-server-badge" data-player-active-server hidden></div>
    <div class="rm-server-selector" data-player-servers aria-label="Stream servers"></div>
</div>

@once
    @push('scripts')
        <script>
        (() => {
            const STARTUP_TIMEOUT_MS = 8000;
            const BUFFERING_GRACE_MS = 5000;
            const LIVE_STALL_RECOVERY_MS = 45000;
            const HARD_RELOAD_AFTER_MS = 90000;
            const MAX_STARTUP_RETRIES = 1;
            const MAX_SOFT_RECOVERIES = 5;
            const SOFT_RECOVERY_DELAYS = [1000, 2000, 3000, 4000, 5000];

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

            const isStartupHttpFailure = (detail, info) => {
                const statusCode = Number(info?.code || info?.status || info?.statusCode || 0);

                return String(detail || '').toLowerCase().includes('httpstatus')
                    || statusCode >= 400;
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
                    this.statusTitle = root.querySelector('[data-player-status-title]');
                    this.statusSubtitle = root.querySelector('[data-player-status-subtitle]');
                    this.activeServerBadge = root.querySelector('[data-player-active-server]');
                    this.retryButton = root.querySelector('[data-player-retry]');
                    this.nextButton = root.querySelector('[data-player-next]');
                    this.externalLink = root.querySelector('[data-player-external]');
                    this.quality = root.querySelector('[data-player-quality]');
                    this.serverSelector = root.querySelector('[data-player-servers]');
                    this.config = JSON.parse(root.dataset.config || '{}');
                    this.sources = this.config.sources || [];
                    this.activeIndex = 0;
                    this.startupRetries = 0;
                    this.softRecoveryCount = 0;
                    this.timeout = null;
                    this.retryTimeout = null;
                    this.bufferingTimeout = null;
                    this.bufferingRecoveryTimeout = null;
                    this.hardReloadTimeout = null;
                    this.resumeTimeout = null;
                    this.loadToken = 0;
                    this.state = 'idle';
                    this.hls = null;
                    this.mpegts = null;
                    this.player = null;
                    this.currentEngine = null;
                    this.currentServer = null;
                    this.hasStartedPlayback = false;
                    this.isLiveStream = true;
                    this.isSwitchingServer = false;
                    this.isManualRetry = false;
                    this.isRecovering = false;
                    this.isHardReloading = false;
                    this.isRecoveryLoad = false;
                    this.hardReloadCount = 0;
                    this.lastPlaybackTime = 0;
                    this.lastProgressAt = Date.now();
                    this.failedIndexes = new Set();
                    this.bufferingIndexes = new Set();

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

                isCurrentToken(token) {
                    return token === this.loadToken;
                }

                async load(index = 0) {
                    const previousIndex = this.activeIndex;
                    const hadLoadedServer = this.loadToken > 0;
                    this.activeIndex = Math.max(0, Math.min(index, this.sources.length - 1));
                    this.startupRetries = 0;
                    this.softRecoveryCount = 0;
                    this.hardReloadCount = 0;
                    this.isHardReloading = false;
                    this.isSwitchingServer = hadLoadedServer && previousIndex !== this.activeIndex;
                    this.clearRetryTimeout();
                    this.renderServers();
                    await this.loadServer();
                }

                async retry(force = false) {
                    this.isManualRetry = true;
                    if (force) {
                        this.startupRetries = 0;
                        this.softRecoveryCount = 0;
                        this.failedIndexes.delete(this.activeIndex);
                    }
                    await this.loadServer();
                }

                async loadServer() {
                    this.loadToken += 1;
                    const token = this.loadToken;
                    const wasLiveRecovering = this.hasStartedPlayback || this.isHardReloading;
                    let source = this.currentSource();
                    const streamType = detectStreamType(source);
                    const label = serverLabel(source, this.activeIndex);
                    this.hasStartedPlayback = false;
                    this.isLiveStream = streamType !== 'mp4' && streamType !== 'dash';
                    this.isRecovering = false;
                    this.isRecoveryLoad = wasLiveRecovering;
                    this.lastPlaybackTime = 0;
                    this.lastProgressAt = Date.now();

                    console.log('[RiFiPlayer] Loading stream', {
                        server: label,
                        type: streamType,
                        engine: this.engineFor(streamType),
                    });

                    if (!source?.url) {
                        this.showError('Channel unavailable', 'No stream URL is configured for this channel.');
                        return;
                    }

                    this.updateExternalLink(source);

                    if (source.requires_external_player && window.location.protocol === 'https:') {
                        if (!source.browser_url) {
                            this.currentServer = source;
                            this.handleStartupFailure('This HTTP-only stream cannot play inside an HTTPS page. Use Open external player or try another source.', false);
                            return;
                        }

                        source = { ...source, url: source.browser_url };
                    }

                    this.currentServer = source;

                    this.cleanupPlayer();
                    this.setPlayerState((this.isSwitchingServer || wasLiveRecovering) ? 'reconnecting' : 'connecting', {
                        title: this.isSwitchingServer
                            ? `Switching to ${label}`
                            : (wasLiveRecovering ? 'Restoring live broadcast' : 'Connecting to broadcast'),
                        subtitle: `${label} - ${streamType.toUpperCase()}`,
                        soft: this.isSwitchingServer || wasLiveRecovering,
                    });
                    this.startTimeout();

                    try {
                        const sourceUrl = String(source.url).toLowerCase().includes('.m3u') && !String(source.url).toLowerCase().includes('.m3u8')
                            ? await firstPlayableFromM3u(source.url)
                            : source.url;

                        if (streamType === 'hls') {
                            this.loadWithHls(sourceUrl, label, token);
                            return;
                        }

                        if (streamType === 'mpegts') {
                            this.loadWithMpegTs(sourceUrl, label, token);
                            return;
                        }

                        this.loadWithNative(sourceUrl, streamType, label, token);
                    } catch (error) {
                        if (!this.isCurrentToken(token)) return;
                        console.error('[RiFiPlayer] Stream preparation failed', {
                            error,
                            server: label,
                            type: streamType,
                        });
                        this.handleStartupFailure(error.message || 'Stream preparation failed.');
                    }
                }

                createFreshVideoElement() {
                    const id = this.root.dataset.playerId || `rifi-player-${Date.now()}`;
                    const video = document.createElement('video');
                    video.id = id;
                    video.className = 'sat-player__video rm-player-video rifi-video-element';
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
                    this.bindNativeVideoEvents(this.loadToken);

                    return video;
                }

                bindNativeVideoEvents(token = this.loadToken) {
                    if (!this.video) return;

                    this.video.addEventListener('loadedmetadata', () => {
                        if (!this.isCurrentToken(token) || this.hasStartedPlayback) return;
                        this.clearTimeout();
                        this.setPlayerState('connecting', {
                            title: this.isRecoveryLoad ? 'Restoring live broadcast' : 'Connecting to broadcast',
                            subtitle: serverLabel(this.currentSource(), this.activeIndex),
                            soft: this.isRecoveryLoad,
                        });
                    });
                    this.video.addEventListener('canplay', () => {
                        if (!this.isCurrentToken(token)) return;
                        this.markPlaybackStarted('canplay');
                        if (this.config.autoplay) this.safePlay();
                    });
                    this.video.addEventListener('playing', () => {
                        if (this.isCurrentToken(token)) this.markPlaybackStarted('playing');
                    });
                    this.video.addEventListener('timeupdate', () => {
                        if (!this.isCurrentToken(token)) return;
                        const currentTime = Number(this.video.currentTime || 0);
                        const moving = Math.abs(currentTime - this.lastPlaybackTime) > 0.05;
                        if (moving || currentTime > 0 || this.video.readyState >= 2) {
                            this.lastPlaybackTime = currentTime;
                            this.markPlaybackStarted('timeupdate');
                        }
                    });
                    this.video.addEventListener('progress', () => {
                        if (!this.isCurrentToken(token)) return;
                        this.lastProgressAt = Date.now();
                    });
                    this.video.addEventListener('waiting', () => {
                        if (this.isCurrentToken(token)) this.handleWaiting();
                    });
                    this.video.addEventListener('stalled', () => {
                        if (this.isCurrentToken(token)) this.handleWaiting();
                    });
                    this.video.addEventListener('suspend', () => {
                        if (!this.isCurrentToken(token)) return;
                        console.debug('[RiFiPlayer] Native video suspend ignored for live stream', {
                            server: serverLabel(this.currentSource(), this.activeIndex),
                        });
                    });
                    this.video.addEventListener('pause', () => {
                        if (this.isCurrentToken(token)) this.handlePause();
                    });
                    this.video.addEventListener('ended', () => {
                        if (this.isCurrentToken(token)) this.handleLiveEnded();
                    });
                    this.video.addEventListener('error', () => {
                        if (!this.isCurrentToken(token)) return;
                        const error = this.video.error;
                        console.error('[RiFiPlayer] Native video error', {
                            code: error?.code,
                            message: error?.message,
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            type: detectStreamType(this.currentSource()),
                        });
                        if (this.hasStartedPlayback && this.isLiveStream) {
                            this.handleRecoverableLiveError(error?.message || 'Live stream interruption.');
                            return;
                        }
                        this.handleStartupFailure(error?.message || 'Video playback failed.');
                    });
                }

                loadWithMpegTs(streamUrl, label, token) {
                    this.createFreshVideoElement();

                    if (!window.mpegts?.isSupported()) {
                        this.handleStartupFailure('MPEG-TS playback is not supported by this browser.');
                        return;
                    }

                    console.log('[RiFiPlayer] Using mpegts.js', { server: label });
                    this.currentEngine = 'mpegts.js';
                    this.setPlayerState('loading', this.loadingDetails(label, 'MPEG-TS'));
                    this.mpegts = window.mpegts.createPlayer({
                        type: 'mpegts',
                        isLive: true,
                        url: streamUrl,
                        cors: true,
                        withCredentials: false,
                    }, {
                        enableWorker: true,
                        lazyLoad: false,
                        stashInitialSize: 1024,
                        liveBufferLatencyChasing: false,
                        autoCleanupSourceBuffer: true,
                        autoCleanupMaxBackwardDuration: 60,
                        autoCleanupMinBackwardDuration: 30,
                        fixAudioTimestampGap: true,
                        accurateSeek: false,
                    });

                    this.mpegts.on(window.mpegts.Events.ERROR, (type, detail, info) => {
                        if (!this.isCurrentToken(token)) return;
                        console.error('[RiFiPlayer] mpegts.js error', { type, detail, info, server: label });
                        if (this.hasStartedPlayback && this.isLiveStream) {
                            this.handleRecoverableLiveError(detail || type || 'MPEG-TS live interruption.');
                            return;
                        }
                        if (isStartupHttpFailure(detail, info)) {
                            this.handleStartupFailure(detail || `Stream request failed with HTTP ${info?.code || 'error'}.`);
                            return;
                        }
                        if (this.isLiveStream) {
                            this.setPlayerState('loading', {
                                title: this.isRecoveryLoad ? 'Restoring live broadcast' : 'Connecting to broadcast',
                                subtitle: `${label} - waiting for IPTV data`,
                                soft: this.isRecoveryLoad,
                            });
                            return;
                        }
                        this.handleStartupFailure(detail || type || 'MPEG-TS playback failed.');
                    });

                    if (window.mpegts.Events.STATISTICS_INFO) {
                        this.mpegts.on(window.mpegts.Events.STATISTICS_INFO, () => {
                            if (!this.isCurrentToken(token)) return;
                            this.clearTimeout();
                            this.lastProgressAt = Date.now();
                        });
                    }

                    this.mpegts.attachMediaElement(this.video);
                    this.mpegts.load();

                    const playPromise = typeof this.mpegts.play === 'function'
                        ? this.mpegts.play()
                        : this.video.play();

                    Promise.resolve(playPromise).catch((error) => {
                        if (!this.isCurrentToken(token)) return;
                        console.warn('[RiFiPlayer] MPEG-TS autoplay blocked or failed', { error, server: label });
                    });
                }

                loadWithHls(streamUrl, label, token) {
                    this.createFreshVideoElement();

                    if (this.video.canPlayType('application/vnd.apple.mpegurl')) {
                        console.log('[RiFiPlayer] Using native HLS', { server: label });
                        this.currentEngine = 'native-hls';
                        this.setPlayerState('loading', this.loadingDetails(label, 'HLS'));
                        this.video.src = streamUrl;
                        this.video.load();
                        this.safePlay();
                        return;
                    }

                    if (!window.Hls?.isSupported()) {
                        this.handleStartupFailure('HLS playback is not supported by this browser.');
                        return;
                    }

                    console.log('[RiFiPlayer] Using hls.js', { server: label });
                    this.currentEngine = 'hls.js';
                    this.setPlayerState('loading', this.loadingDetails(label, 'HLS'));
                    this.hls = new Hls({
                        liveSyncDurationCount: 6,
                        liveMaxLatencyDurationCount: 12,
                        maxBufferLength: 60,
                        maxMaxBufferLength: 120,
                        backBufferLength: 60,
                        enableWorker: true,
                        lowLatencyMode: false,
                        manifestLoadingTimeOut: STARTUP_TIMEOUT_MS,
                        fragLoadingTimeOut: STARTUP_TIMEOUT_MS,
                    });

                    this.hls.on(Hls.Events.MANIFEST_PARSED, (_, data) => {
                        if (!this.isCurrentToken(token)) return;
                        this.populateQuality(data.levels || []);
                        this.safePlay();
                    });

                    this.hls.on(Hls.Events.LEVEL_SWITCHED, () => {
                        if (!this.isCurrentToken(token)) return;
                        if (this.quality && this.hls) this.quality.value = String(this.hls.currentLevel);
                    });

                    this.hls.on(Hls.Events.ERROR, (_, data) => {
                        if (!this.isCurrentToken(token)) return;
                        console.error('[RiFiPlayer] HLS.js error', { data, server: label });

                        if (!data.fatal) return;
                        if (data.type === Hls.ErrorTypes.NETWORK_ERROR && this.hls) {
                            if (this.hasStartedPlayback) {
                                this.handleRecoverableLiveError(data.details || 'HLS network interruption.', false);
                            }
                            this.hls.startLoad();
                            return;
                        }
                        if (data.type === Hls.ErrorTypes.MEDIA_ERROR && this.hls) {
                            if (this.hasStartedPlayback) {
                                this.handleRecoverableLiveError(data.details || 'HLS media interruption.', false);
                            }
                            this.hls.recoverMediaError();
                            return;
                        }

                        if (this.hasStartedPlayback && this.isLiveStream) {
                            this.handleRecoverableLiveError(data.details || 'Fatal HLS playback error.');
                            return;
                        }
                        this.handleStartupFailure(data.details || 'Fatal HLS playback error.');
                    });

                    this.hls.loadSource(streamUrl);
                    this.hls.attachMedia(this.video);
                }

                loadWithNative(streamUrl, streamType, label, token) {
                    this.createFreshVideoElement();
                    console.log('[RiFiPlayer] Using native video', { server: label, type: streamType });
                    this.currentEngine = 'native';
                    this.setPlayerState('loading', this.loadingDetails(label, streamType.toUpperCase()));
                    this.video.src = streamUrl;
                    this.video.type = mimeTypeFor(streamType);
                    this.video.load();
                    this.safePlay();
                }

                safePlay() {
                    if (!this.video) return;
                    if (!this.config.autoplay) {
                        return;
                    }

                    this.video.play().catch((error) => {
                        console.warn('[RiFiPlayer] Autoplay blocked or failed', error);
                    });
                }

                handleStartupFailure(message, retryable = true) {
                    this.clearTimeout();
                    this.clearBufferingTimeout();
                    this.failedIndexes.add(this.activeIndex);
                    this.bufferingIndexes.delete(this.activeIndex);
                    this.renderServers();
                    const failedLabel = serverLabel(this.currentSource(), this.activeIndex);

                    if (retryable && this.startupRetries < MAX_STARTUP_RETRIES) {
                        this.startupRetries += 1;
                        console.warn('[RiFiPlayer] Retrying failed server startup', { server: failedLabel, retry: this.startupRetries, reason: message });
                        this.setPlayerState('reconnecting', {
                            title: 'Reconnecting broadcast',
                            subtitle: `${failedLabel} failed. Retrying once...`,
                            soft: true,
                        });
                        const token = this.loadToken;
                        this.retryTimeout = setTimeout(() => {
                            if (this.isCurrentToken(token)) this.loadServer();
                        }, 900 * this.startupRetries);
                        return;
                    }

                    const nextIndex = this.nextAvailableIndex();
                    if (nextIndex !== null) {
                        const nextLabel = serverLabel(this.sources[nextIndex], nextIndex);
                        console.warn('[RiFiPlayer] Server failed, trying fallback', {
                            failed: failedLabel,
                            next: nextLabel,
                            reason: message,
                        });
                        this.activeIndex = nextIndex;
                        this.startupRetries = 0;
                        this.setPlayerState('switching_server', {
                            title: `${failedLabel} failed`,
                            subtitle: `Trying ${nextLabel}...`,
                            soft: true,
                        });
                        this.renderServers();
                        const token = this.loadToken;
                        this.retryTimeout = setTimeout(() => {
                            if (this.isCurrentToken(token)) this.loadServer();
                        }, 800);
                        return;
                    }

                    this.showError('Broadcast unavailable', `We could not start this stream. ${failedLabel} failed. ${message || ''}`.trim());
                }

                startTimeout() {
                    this.clearTimeout();
                    this.timeout = setTimeout(() => {
                        console.error('[RiFiPlayer] Startup timeout', {
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            type: detectStreamType(this.currentSource()),
                            timeout_ms: STARTUP_TIMEOUT_MS,
                        });
                        this.handleStartupFailure(`This stream is unavailable. Try another source.`);
                    }, STARTUP_TIMEOUT_MS);
                }

                clearTimeout() {
                    if (this.timeout) {
                        clearTimeout(this.timeout);
                        this.timeout = null;
                    }
                }

                clearRetryTimeout() {
                    if (this.retryTimeout) {
                        clearTimeout(this.retryTimeout);
                        this.retryTimeout = null;
                    }
                }

                clearBufferingTimeout() {
                    if (this.bufferingTimeout) {
                        clearTimeout(this.bufferingTimeout);
                        this.bufferingTimeout = null;
                    }
                    if (this.bufferingRecoveryTimeout) {
                        clearTimeout(this.bufferingRecoveryTimeout);
                        this.bufferingRecoveryTimeout = null;
                    }
                    this.bufferingIndexes.delete(this.activeIndex);
                }

                clearHardReloadTimeout() {
                    if (this.hardReloadTimeout) {
                        clearTimeout(this.hardReloadTimeout);
                        this.hardReloadTimeout = null;
                    }
                }

                clearResumeTimeout() {
                    if (this.resumeTimeout) {
                        clearTimeout(this.resumeTimeout);
                        this.resumeTimeout = null;
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

                loadingDetails(label, type) {
                    if (this.isSwitchingServer) {
                        return {
                            title: `Switching to ${label}`,
                            subtitle: `${label} - ${type}`,
                            soft: true,
                        };
                    }

                    return {
                        title: this.isRecoveryLoad ? 'Restoring live broadcast' : 'Connecting to broadcast',
                        subtitle: `${label} - ${type}`,
                        soft: this.isRecoveryLoad,
                    };
                }

                showLoading() {
                    this.setPlayerState('loading', {
                        title: this.hasStartedPlayback || this.isRecoveryLoad ? 'Restoring live broadcast' : 'Connecting to broadcast',
                        subtitle: serverLabel(this.currentSource(), this.activeIndex),
                        soft: this.hasStartedPlayback || this.isRecoveryLoad,
                    });
                }

                showReady() {
                    this.markPlaybackStarted('ready');
                }

                markPlaybackStarted(reason = 'playing') {
                    const wasAlreadyPlaying = this.hasStartedPlayback && this.state === 'playing';
                    this.hasStartedPlayback = true;
                    this.isSwitchingServer = false;
                    this.isManualRetry = false;
                    this.isRecovering = false;
                    this.isHardReloading = false;
                    this.isRecoveryLoad = false;
                    this.softRecoveryCount = 0;
                    this.hardReloadCount = 0;
                    this.lastProgressAt = Date.now();
                    this.clearTimeout();
                    this.clearBufferingTimeout();
                    this.clearRetryTimeout();
                    this.clearHardReloadTimeout();
                    this.clearResumeTimeout();
                    this.state = 'playing';
                    this.failedIndexes.delete(this.activeIndex);
                    this.bufferingIndexes.delete(this.activeIndex);
                    this.root.classList.remove('is-loading', 'has-error');
                    this.root.dataset.playerState = 'playing';
                    if (this.loading) this.loading.hidden = true;
                    if (this.error) this.error.hidden = true;
                    this.renderServers();
                    this.updateActiveServerBadge();
                    if (!wasAlreadyPlaying) {
                        console.log('[RiFiPlayer] Playback started', {
                            server: serverLabel(this.currentSource(), this.activeIndex),
                            engine: this.currentEngine,
                            reason,
                        });
                    }
                }

                showError(title, message) {
                    this.cleanupPlayer();
                    this.state = 'unavailable';
                    this.root.classList.remove('is-loading');
                    this.root.classList.add('has-error');
                    this.root.dataset.playerState = 'unavailable';
                    if (this.loading) this.loading.hidden = true;
                    if (this.error) this.error.hidden = false;
                    const errorTitle = this.root.querySelector('[data-player-error-title]');
                    if (errorTitle) errorTitle.textContent = title;
                    if (this.errorMessage) this.errorMessage.textContent = message;
                }

                cleanupPlayer() {
                    this.clearTimeout();
                    this.clearRetryTimeout();
                    this.clearBufferingTimeout();
                    this.clearHardReloadTimeout();
                    this.clearResumeTimeout();
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

                scheduleBufferingOverlay() {
                    if (!this.hasStartedPlayback || !this.isLiveStream || this.state === 'reconnecting') return;

                    const token = this.loadToken;
                    const progressAtStart = this.lastProgressAt;
                    this.clearBufferingTimeout();
                    this.bufferingTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token)) return;
                        this.bufferingIndexes.add(this.activeIndex);
                        this.renderServers();
                        this.setPlayerState('buffering', {
                            title: 'Buffering live signal',
                            subtitle: `${serverLabel(this.currentSource(), this.activeIndex)} is catching up.`,
                            soft: true,
                        });
                    }, BUFFERING_GRACE_MS);

                    this.bufferingRecoveryTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token)) return;
                        if (this.lastProgressAt <= progressAtStart) {
                            this.handleLiveGap('No playback progress while buffering.');
                        }
                    }, LIVE_STALL_RECOVERY_MS);

                    this.hardReloadTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token)) return;
                        if (this.lastProgressAt <= progressAtStart) {
                            this.scheduleHardReload('live stall exceeded hard reload grace');
                        }
                    }, HARD_RELOAD_AFTER_MS);
                }

                handleWaiting() {
                    if (this.hasStartedPlayback) {
                        this.scheduleBufferingOverlay();
                        return;
                    }

                    this.setPlayerState('connecting', {
                        title: this.isRecoveryLoad ? 'Restoring live broadcast' : 'Connecting to broadcast',
                        subtitle: serverLabel(this.currentSource(), this.activeIndex),
                        soft: this.isRecoveryLoad,
                    });
                }

                handlePause() {
                    if (!this.isLiveStream || !this.hasStartedPlayback || this.isSwitchingServer || this.isRecovering) return;
                    const token = this.loadToken;
                    this.clearResumeTimeout();
                    this.resumeTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token) || !this.video?.paused || this.video?.ended) return;
                        this.video.play().catch((error) => {
                            console.warn('[RiFiPlayer] Gentle live resume failed', error);
                        });
                    }, 650);
                }

                handleLiveEnded() {
                    if (!this.isLiveStream) {
                        this.setPlayerState('idle', {
                            title: 'Playback ended',
                            subtitle: serverLabel(this.currentSource(), this.activeIndex),
                            soft: true,
                        });
                        return;
                    }

                    if (this.isSwitchingServer) return;

                    console.warn('[RiFiPlayer] Live stream ended event treated as recoverable interruption', {
                        server: serverLabel(this.currentSource(), this.activeIndex),
                        engine: this.currentEngine,
                    });

                    this.handleLiveGap('ended');
                }

                handleLiveGap(reason = 'live gap') {
                    if (!this.isLiveStream) return;

                    this.setPlayerState('reconnecting', {
                        title: 'Restoring live broadcast',
                        subtitle: 'Keeping the live stream active',
                        soft: true,
                    });

                    this.attemptSoftReconnect(reason);
                }

                handleRecoverableLiveError(message, scheduleReconnect = true) {
                    if (!this.isLiveStream) {
                        this.handleStartupFailure(message);
                        return;
                    }

                    if (this.isRecovering && scheduleReconnect) return;

                    this.clearTimeout();
                    this.clearBufferingTimeout();
                    this.setPlayerState('reconnecting', {
                        title: 'Reconnecting live signal',
                        subtitle: `${serverLabel(this.currentSource(), this.activeIndex)} - ${message}`,
                        soft: true,
                    });

                    if (scheduleReconnect) {
                        this.attemptSoftReconnect(message);
                    } else {
                        this.isRecovering = false;
                    }
                }

                attemptSoftReconnect(reason = 'soft recovery') {
                    const token = this.loadToken;

                    if (this.isRecovering) return;

                    if (this.softRecoveryCount >= MAX_SOFT_RECOVERIES) {
                        this.isRecovering = false;
                        this.scheduleHardReload('soft recoveries exhausted');
                        return;
                    }

                    const attempt = this.softRecoveryCount + 1;
                    const delay = SOFT_RECOVERY_DELAYS[this.softRecoveryCount] || SOFT_RECOVERY_DELAYS[SOFT_RECOVERY_DELAYS.length - 1];
                    this.softRecoveryCount = attempt;
                    this.isRecovering = true;

                    console.warn('[RiFiPlayer] Soft reconnect scheduled', {
                        server: serverLabel(this.currentSource(), this.activeIndex),
                        attempt,
                        delay_ms: delay,
                        reason,
                    });

                    this.clearRetryTimeout();
                    this.retryTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token)) return;
                        this.performSoftReconnect(token);
                    }, delay);
                }

                performSoftReconnect(token) {
                    if (!this.isCurrentToken(token)) return;
                    const progressAtStart = this.lastProgressAt;

                    this.setPlayerState('recovering', {
                        title: 'Restoring broadcast',
                        subtitle: `${serverLabel(this.currentSource(), this.activeIndex)} - attempt ${this.softRecoveryCount}`,
                        soft: true,
                    });

                    const playPromise = this.video?.play?.();
                    Promise.resolve(playPromise).then(() => {
                        if (!this.isCurrentToken(token)) return;
                        this.isRecovering = false;
                        this.scheduleRecoveryCheck(token, progressAtStart);
                    }).catch(() => {
                        if (!this.isCurrentToken(token)) return;

                        if (this.currentEngine === 'hls.js' && this.hls) {
                            this.hls.startLoad();
                            this.safePlay();
                            this.isRecovering = false;
                            this.scheduleRecoveryCheck(token, progressAtStart);
                            return;
                        }

                        if (this.currentEngine === 'mpegts.js' && this.mpegts) {
                            try {
                                this.mpegts.load();
                                this.mpegts.play();
                                this.isRecovering = false;
                                this.scheduleRecoveryCheck(token, progressAtStart);
                                return;
                            } catch (error) {
                                console.warn('[RiFiPlayer] mpegts soft reload failed', error);
                            }
                        }

                        this.isRecovering = false;
                        this.attemptSoftReconnect();
                    });
                }

                scheduleHardReload(reason = 'hard reload grace exceeded') {
                    const token = this.loadToken;

                    if (this.isHardReloading) return;
                    if (this.hardReloadCount >= 1) {
                        this.failCurrentServer(reason);
                        return;
                    }

                    this.isHardReloading = true;
                    this.hardReloadCount += 1;
                    this.setPlayerState('recovering', {
                        title: 'Restoring live broadcast',
                        subtitle: 'Rebuilding the stream engine without leaving this page.',
                        soft: true,
                    });

                    console.warn('[RiFiPlayer] Hard engine reload scheduled', {
                        server: serverLabel(this.currentSource(), this.activeIndex),
                        reason,
                    });

                    this.clearRetryTimeout();
                    this.retryTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token)) return;
                        this.startupRetries = 0;
                        this.softRecoveryCount = 0;
                        this.loadServer();
                    }, 900);
                }

                failCurrentServer(reason = 'server failed') {
                    this.isRecovering = false;
                    this.isHardReloading = false;
                    this.failedIndexes.add(this.activeIndex);
                    const nextIndex = this.nextAvailableIndex();

                    if (nextIndex !== null) {
                        const token = this.loadToken;
                        const nextLabel = serverLabel(this.sources[nextIndex], nextIndex);
                        this.activeIndex = nextIndex;
                        this.startupRetries = 0;
                        this.softRecoveryCount = 0;
                        this.hardReloadCount = 0;
                        this.isSwitchingServer = true;
                        this.setPlayerState('reconnecting', {
                            title: `Switching to ${nextLabel}`,
                            subtitle: `Current server could not recover: ${reason}`,
                            soft: true,
                        });
                        this.renderServers();
                        this.retryTimeout = setTimeout(() => {
                            if (this.isCurrentToken(token)) this.loadServer();
                        }, 900);
                        return;
                    }

                    this.showError('Broadcast unavailable', 'We could not restore this live signal after extended recovery attempts.');
                }

                scheduleRecoveryCheck(token, progressAtStart) {
                    this.clearRetryTimeout();
                    this.retryTimeout = setTimeout(() => {
                        if (!this.isCurrentToken(token)) return;
                        if (this.lastProgressAt <= progressAtStart) {
                            this.attemptSoftReconnect();
                        }
                    }, 6200);
                }

                setPlayerState(state, details = {}) {
                    this.state = state;
                    this.root.dataset.playerState = state;
                    this.root.classList.toggle('is-loading', !['idle', 'playing', 'failed', 'unavailable'].includes(state));
                    this.root.classList.toggle('has-error', ['failed', 'unavailable'].includes(state));

                    if (this.loading) {
                        this.loading.hidden = ['idle', 'playing', 'failed', 'unavailable'].includes(state);
                        this.loading.classList.toggle('is-soft', Boolean(details.soft));
                    }

                    if (this.error) {
                        this.error.hidden = !['failed', 'unavailable'].includes(state);
                    }

                    if (this.statusTitle && details.title) {
                        this.statusTitle.textContent = details.title;
                    }

                    if (this.statusSubtitle) {
                        this.statusSubtitle.textContent = details.subtitle || serverLabel(this.currentSource(), this.activeIndex);
                    }

                    this.updateActiveServerBadge();
                }

                updateActiveServerBadge() {
                    if (!this.activeServerBadge) return;

                    const source = this.currentSource();
                    if (!source) {
                        this.activeServerBadge.hidden = true;
                        return;
                    }

                    const label = serverLabel(source, this.activeIndex);
                    const type = detectStreamType(source).toUpperCase();
                    const quality = source.quality || 'Auto';
                    this.activeServerBadge.innerHTML = `<strong>${escapeHtml(label)}</strong><span>${escapeHtml(quality)} - ${escapeHtml(type)}</span>`;
                    this.activeServerBadge.hidden = false;
                }

                updateExternalLink(source = this.currentSource()) {
                    if (!this.externalLink) return;

                    if (source?.url) {
                        this.externalLink.href = source.external_url || source.url;
                        this.externalLink.hidden = false;
                    } else {
                        this.externalLink.hidden = true;
                    }
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
                        const buffering = this.bufferingIndexes.has(index) ? ' is-buffering' : '';
                        const status = failed ? 'failed' : (buffering ? 'buffering' : (active ? 'active' : health));

                        return `<button type="button" class="rm-server-option${active}${failed}${buffering}" data-server-index="${index}" aria-label="Use ${escapeHtml(label)}">
                            <i class="rifi-status-dot rifi-status-dot--${escapeHtml(status)}"></i>
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

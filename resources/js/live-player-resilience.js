import { t } from './i18n';

const RETRY_DELAYS = [750, 1500, 3000, 6000, 10000, 15000];
const STALL_TIMEOUT = 20000;
let activePlayer = null;

const extractHttpStatus = (value, depth = 0) => {
    if (depth > 3 || value === null || value === undefined) return null;
    if (typeof value === 'number' && value >= 100 && value <= 599) return value;
    if (typeof value === 'string') {
        const match = value.match(/\b([1-5]\d{2})\b/);
        return match ? Number(match[1]) : null;
    }
    if (Array.isArray(value)) {
        for (const entry of value) {
            const status = extractHttpStatus(entry, depth + 1);
            if (status) return status;
        }
        return null;
    }
    if (typeof value === 'object') {
        for (const key of ['status', 'statusCode', 'code', 'response', 'message', 'msg']) {
            const status = extractHttpStatus(value[key], depth + 1);
            if (status) return status;
        }
    }
    return null;
};

const debug = (message, context = {}) => {
    if (!['localhost', '127.0.0.1'].includes(window.location.hostname)) return;
    console.debug(`[RifiLiveTV] ${message}`, context);
};

export function initResilientPlayer(video, streamUrl, options = {}) {
    if (!(video instanceof HTMLVideoElement) || !streamUrl) {
        throw new Error(t('A video element and protected stream URL are required.'));
    }

    destroyResilientPlayer();

    const Hls = options.Hls || window.Hls;
    const mpegts = options.mpegts || window.mpegts;
    const streamType = String(options.streamType || '').toLowerCase();
    const listeners = [];
    let hls = null;
    let mpegPlayer = null;
    let retryTimer = null;
    let healthTimer = null;
    let stableTimer = null;
    let destroyed = false;
    let retryCount = 0;
    let hlsNetworkRecoveries = 0;
    let hlsMediaRecoveries = 0;
    let lastProgressAt = Date.now();
    let lastCurrentTime = 0;
    let engineKind = 'native';
    let nativeSoftRecoveries = 0;
    let forbiddenHandled = false;

    const notify = (name, payload) => options[name]?.(payload);
    const addListener = (target, event, handler, settings) => {
        target.addEventListener(event, handler, settings);
        listeners.push(() => target.removeEventListener(event, handler, settings));
    };

    const cleanupEngine = () => {
        if (hls) {
            hls.destroy();
            hls = null;
        }
        if (mpegPlayer) {
            try {
                mpegPlayer.unload();
                mpegPlayer.detachMediaElement();
                mpegPlayer.destroy();
            } catch (error) {
                debug('MPEG-TS cleanup skipped.', { message: error?.message });
            }
            mpegPlayer = null;
        }
    };

    const resetMedia = () => {
        video.pause();
        video.removeAttribute('src');
        video.load();
    };

    const play = () => {
        video.play().catch((error) => {
            debug('Autoplay is waiting for user interaction.', { message: error?.message });
            notify('onAutoplayBlocked');
        });
    };

    const fatal = (message) => {
        clearTimeout(retryTimer);
        retryTimer = null;
        notify('onFatal', message);
    };

    const handleForbidden = (details) => {
        if (forbiddenHandled || destroyed) return false;
        const status = extractHttpStatus(details);
        if (status !== 401 && status !== 403) return false;

        forbiddenHandled = true;
        clearTimeout(retryTimer);
        clearTimeout(stableTimer);
        retryTimer = null;
        stableTimer = null;
        cleanupEngine();
        resetMedia();
        notify('onForbidden', status);

        return true;
    };

    const load = () => {
        if (destroyed) return;
        cleanupEngine();
        resetMedia();
        notify('onLoading');

        const isHls = ['hls', 'm3u', 'm3u8'].includes(streamType)
            || streamUrl.toLowerCase().includes('.m3u');
        const isMpegTs = ['mpegts', 'ts', 'stream'].includes(streamType);

        if (isHls && Hls?.isSupported()) {
            engineKind = 'hls';
            hls = new Hls({
                enableWorker: true,
                lowLatencyMode: false,
                backBufferLength: 90,
                maxBufferLength: 30,
                maxMaxBufferLength: 60,
                liveSyncDurationCount: 4,
                liveMaxLatencyDurationCount: 10,
                fragLoadingTimeOut: 20000,
                fragLoadingMaxRetry: 6,
                fragLoadingRetryDelay: 1000,
                fragLoadingMaxRetryTimeout: 8000,
                manifestLoadingTimeOut: 20000,
                manifestLoadingMaxRetry: 6,
                manifestLoadingRetryDelay: 1000,
                levelLoadingTimeOut: 20000,
                levelLoadingMaxRetry: 6,
                levelLoadingRetryDelay: 1000,
            });
            hls.loadSource(streamUrl);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, play);
            hls.on(Hls.Events.ERROR, (_, data) => {
                debug('HLS event.', {
                    type: data.type,
                    details: data.details,
                    fatal: data.fatal,
                    responseCode: data.response?.code,
                });
                if (handleForbidden(data) || !data.fatal || destroyed) return;

                if (data.type === Hls.ErrorTypes.NETWORK_ERROR && hlsNetworkRecoveries < 2) {
                    hlsNetworkRecoveries += 1;
                    notify('onReconnecting', t('Reconnecting to the live stream...'));
                    hls.startLoad();
                    return;
                }
                if (data.type === Hls.ErrorTypes.MEDIA_ERROR && hlsMediaRecoveries < 2) {
                    hlsMediaRecoveries += 1;
                    notify('onReconnecting', t('Repairing video playback...'));
                    hls.recoverMediaError();
                    return;
                }
                scheduleReconnect(`HLS ${data.details || data.type || 'playback'} error`);
            });
            return;
        }

        if (isHls && video.canPlayType('application/vnd.apple.mpegurl')) {
            engineKind = 'native';
            video.src = streamUrl;
            video.load();
            play();
            return;
        }

        if (isMpegTs && mpegts?.isSupported()) {
            engineKind = 'mpegts';
            mpegPlayer = mpegts.createPlayer({
                type: 'mpegts',
                isLive: true,
                url: streamUrl,
                cors: true,
                withCredentials: false,
            }, {
                enableWorker: false,
                lazyLoad: false,
                autoCleanupSourceBuffer: true,
                autoCleanupMaxBackwardDuration: 45,
                autoCleanupMinBackwardDuration: 20,
                stashInitialSize: 384 * 1024,
            });
            mpegPlayer.attachMediaElement(video);
            mpegPlayer.on(mpegts.Events.ERROR, (...details) => {
                debug('MPEG-TS playback error.', { details });
                if (handleForbidden(details)) return;
                scheduleReconnect('MPEG-TS playback error');
            });
            mpegPlayer.load();
            mpegPlayer.play().catch(() => {});
            return;
        }

        engineKind = 'native';
        video.src = streamUrl;
        video.load();
        play();
    };

    const scheduleReconnect = (reason, immediate = false) => {
        if (destroyed || retryTimer) return;
        if (!navigator.onLine) {
            notify('onReconnecting', t('No connection'));
            return;
        }
        if (retryCount >= RETRY_DELAYS.length) {
            fatal(t('The stream could not be restored automatically. Try again or choose another channel.'));
            return;
        }

        const delay = immediate ? 0 : RETRY_DELAYS[retryCount];
        retryCount += 1;
        notify('onReconnecting', t('Reconnecting... attempt :attempt of :total', {
            attempt: retryCount,
            total: RETRY_DELAYS.length,
        }));
        debug('Scheduling playback recovery.', { reason, attempt: retryCount, delay });
        clearTimeout(stableTimer);
        cleanupEngine();
        retryTimer = window.setTimeout(() => {
            retryTimer = null;
            load();
        }, delay);
    };

    const recover = (reason) => {
        if (engineKind !== 'native' || nativeSoftRecoveries >= 2) {
            scheduleReconnect(reason);
            return;
        }

        nativeSoftRecoveries += 1;
        notify('onReconnecting', t('Reconnecting...'));
        debug('Trying native soft recovery.', { reason, attempt: nativeSoftRecoveries });
        video.load();
        play();
        window.setTimeout(() => {
            if (!destroyed && Date.now() - lastProgressAt >= STALL_TIMEOUT) {
                scheduleReconnect(`${reason} after soft recovery`);
            }
        }, 3000);
    };

    const markProgress = () => {
        if (video.currentTime > lastCurrentTime + 0.05) {
            lastCurrentTime = video.currentTime;
            lastProgressAt = Date.now();
        }
    };

    addListener(video, 'playing', () => {
        lastCurrentTime = video.currentTime;
        lastProgressAt = Date.now();
        notify('onPlaying');
        clearTimeout(stableTimer);
        stableTimer = window.setTimeout(() => {
            retryCount = 0;
            nativeSoftRecoveries = 0;
            hlsNetworkRecoveries = 0;
            hlsMediaRecoveries = 0;
        }, 60000);
    });
    addListener(video, 'canplay', () => {
        lastProgressAt = Date.now();
        notify('onCanPlay');
    });
    addListener(video, 'timeupdate', markProgress);
    addListener(video, 'waiting', () => notify('onReconnecting', t('The live stream is buffering...')));
    addListener(video, 'stalled', () => {
        notify('onReconnecting', t('Playback stalled. Recovering...'));
        recover('stalled event');
    });
    addListener(video, 'suspend', () => debug('Browser suspended media loading.'));
    addListener(video, 'emptied', () => debug('Media buffer emptied.'));
    addListener(video, 'pause', () => debug('Playback paused.', { ended: video.ended }));
    addListener(video, 'ended', () => recover('stream ended'));
    addListener(video, 'error', () => recover(`media error ${video.error?.code || 'unknown'}`));
    addListener(window, 'offline', () => {
        clearTimeout(retryTimer);
        retryTimer = null;
        notify('onReconnecting', t('No connection'));
    });
    addListener(window, 'online', () => scheduleReconnect('connection restored', true));
    addListener(document, 'visibilitychange', () => {
        if (!document.hidden && (video.error || (!video.paused && Date.now() - lastProgressAt >= STALL_TIMEOUT))) {
            scheduleReconnect('page became visible', true);
        }
    });

    healthTimer = window.setInterval(() => {
        if (destroyed || document.hidden || video.paused || video.ended) return;
        markProgress();
        if (Date.now() - lastProgressAt >= STALL_TIMEOUT) {
            recover('playback health timeout');
        }
    }, 10000);

    load();

    const controller = {
        reconnect(reason = 'manual retry') {
            retryCount = 0;
            scheduleReconnect(reason, true);
        },
        showReconnectOverlay(message = t('Reconnecting...')) {
            notify('onReconnecting', message);
        },
        hideReconnectOverlay() {
            notify('onPlaying');
        },
        destroy() {
            destroyed = true;
            clearTimeout(retryTimer);
            clearTimeout(stableTimer);
            clearInterval(healthTimer);
            retryTimer = null;
            stableTimer = null;
            healthTimer = null;
            listeners.splice(0).forEach((remove) => remove());
            cleanupEngine();
            resetMedia();
            if (activePlayer === controller) activePlayer = null;
        },
    };

    activePlayer = controller;

    return controller;
}

export function destroyResilientPlayer() {
    activePlayer?.destroy();
    activePlayer = null;
}

export function reconnectPlayer(reason = 'manual retry') {
    activePlayer?.reconnect(reason);
}

export function showReconnectOverlay(message = t('Reconnecting...')) {
    activePlayer?.showReconnectOverlay(message);
}

export function hideReconnectOverlay() {
    activePlayer?.hideReconnectOverlay();
}

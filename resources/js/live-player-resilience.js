import { t } from './i18n.js';
import { detectStreamType, getBufferedAhead, toAbsoluteUrl } from './stream-detection.js';

const MAX_RETRIES_PER_SOURCE = 2;
const STALL_TIMEOUT_MS = 20000;
const RETRY_BASE_DELAY_MS = 3000;
const MIN_HEALTHY_BUFFER_SECONDS = 2;
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

    const finalStreamUrl = toAbsoluteUrl(streamUrl);
    debug('Final player stream URL.', {
        url: finalStreamUrl,
        sourceId: options.sourceId ?? null,
    });

    const Hls = options.Hls || window.Hls;
    const mpegts = options.mpegts || window.mpegts;
    const detectedType = detectStreamType(finalStreamUrl, options.streamType);
    const listeners = [];
    let hls = null;
    let mpegPlayer = null;
    let retryTimer = null;
    let healthTimer = null;
    let stableTimer = null;
    let finalCheckTimer = null;
    let destroyed = false;
    let retryCount = 0;
    const maxReconnects = Math.max(3, Number(options.maxReconnects ?? MAX_RETRIES_PER_SOURCE));
    let hlsNetworkRecoveries = 0;
    let hlsMediaRecoveries = 0;
    let lastProgressAt = Date.now();
    let lastCurrentTime = 0;
    let engineKind = 'native';
    let engineOverride = null;
    let unknownEngineFallbackUsed = false;
    let forbiddenHandled = false;
    let autoplayBlocked = false;
    let fatalErrorSeen = false;
    let finalErrorShown = false;
    let lastError = null;
    let lastRecoveryAction = 'initializing';
    let finalFailureMessage = null;

    const notify = (name, payload) => options[name]?.(payload);
    const addListener = (target, event, handler, settings) => {
        target.addEventListener(event, handler, settings);
        listeners.push(() => target.removeEventListener(event, handler, settings));
    };
    const debugState = () => ({
        sourceId: options.sourceId ?? null,
        channelId: options.channelId ?? null,
        detectedType,
        engine: engineKind,
        retryCount,
        maxRetries: maxReconnects,
        readyState: video.readyState,
        networkState: video.networkState,
        bufferedAhead: Number(getBufferedAhead(video).toFixed(2)),
        lastProgressSecondsAgo: Math.max(0, Math.round((Date.now() - lastProgressAt) / 1000)),
        lastError,
        lastRecoveryAction,
        autoplayBlocked,
    });
    const publishDebug = () => notify('onDebug', debugState());
    const recordAction = (action, error = null) => {
        lastRecoveryAction = action;
        if (error) lastError = error;
        publishDebug();
    };

    const cleanupEngine = () => {
        if (hls) {
            hls.destroy();
            hls = null;
        }
        if (mpegPlayer) {
            try {
                mpegPlayer.pause();
            } catch {
                // Some mpegts.js states do not expose a pausable media element.
            }
            try {
                mpegPlayer.unload();
            } catch {
                // Cleanup must continue even when the provider closes first.
            }
            try {
                mpegPlayer.detachMediaElement();
            } catch {
                // Cleanup must continue even when attachment already ended.
            }
            try {
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

    const safePlay = async () => {
        autoplayBlocked = false;
        try {
            await video.play();
            return;
        } catch (error) {
            debug('Initial autoplay was blocked.', { message: error?.message });
        }

        video.muted = true;
        try {
            await video.play();
        } catch (error) {
            autoplayBlocked = true;
            recordAction('waiting for user playback gesture', error?.message);
            notify('onAutoplayBlocked', t('Tap to start playback'));
        }
    };

    const isMixedContent = () => (
        window.location.protocol === 'https:'
        && String(finalStreamUrl).toLowerCase().startsWith('http:')
    );
    const isCrossOriginDirectSource = () => {
        try {
            return new URL(finalStreamUrl, window.location.origin).origin !== window.location.origin;
        } catch {
            return false;
        }
    };

    const isReallyStalled = () => (
        Date.now() - lastProgressAt > STALL_TIMEOUT_MS
        && video.readyState < HTMLMediaElement.HAVE_FUTURE_DATA
        && getBufferedAhead(video) < MIN_HEALTHY_BUFFER_SECONDS
    );

    const fatal = (message) => {
        if (finalErrorShown || destroyed) return;
        finalErrorShown = true;
        clearTimeout(retryTimer);
        clearTimeout(finalCheckTimer);
        retryTimer = null;
        finalCheckTimer = null;
        recordAction('source exhausted', message);
        notify('onFatal', message);
    };

    const finalizeWhenProven = () => {
        if (destroyed || finalErrorShown || retryCount < maxReconnects || !fatalErrorSeen) return;
        if (isReallyStalled()) {
            fatal(finalFailureMessage || t('We tried reconnecting and switching sources. Try again, choose another source, or open another channel.'));
            return;
        }

        const remaining = Math.max(1000, STALL_TIMEOUT_MS - (Date.now() - lastProgressAt) + 250);
        clearTimeout(finalCheckTimer);
        finalCheckTimer = window.setTimeout(finalizeWhenProven, remaining);
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
        recordAction('protected URL rejected', `HTTP ${status}`);
        notify('onForbidden', status);

        return true;
    };

    const currentEngineType = () => engineOverride || detectedType;

    const tryUnknownEngineFallback = (reason) => {
        if (detectedType !== 'auto' || unknownEngineFallbackUsed || !mpegts?.isSupported()) return false;
        unknownEngineFallbackUsed = true;
        engineOverride = 'mpegts';
        recordAction('unknown source switched from HLS probe to MPEG-TS', reason);
        notify('onReconnecting', t('Trying another compatible player...'));
        cleanupEngine();
        retryTimer = window.setTimeout(() => {
            retryTimer = null;
            load();
        }, RETRY_BASE_DELAY_MS);
        return true;
    };

    const loadHls = () => {
        engineKind = 'hls';
        hls = new Hls({
            enableWorker: true,
            lowLatencyMode: false,
            backBufferLength: 30,
            maxBufferLength: 60,
            maxMaxBufferLength: 120,
            manifestLoadingTimeOut: 25000,
            manifestLoadingMaxRetry: 8,
            manifestLoadingRetryDelay: 1000,
            levelLoadingTimeOut: 25000,
            levelLoadingMaxRetry: 8,
            fragLoadingTimeOut: 35000,
            fragLoadingMaxRetry: 10,
            fragLoadingRetryDelay: 1000,
            startFragPrefetch: true,
        });
        hls.loadSource(finalStreamUrl);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, safePlay);
        hls.on(Hls.Events.ERROR, (_, data) => {
            debug('HLS event.', {
                type: data.type,
                details: data.details,
                fatal: data.fatal,
                responseCode: data.response?.code,
            });
            if (handleForbidden(data) || !data.fatal || destroyed) return;

            fatalErrorSeen = true;
            const reason = `HLS ${data.details || data.type || 'playback'} error`;
            if (data.type === Hls.ErrorTypes.NETWORK_ERROR && hlsNetworkRecoveries < 3) {
                hlsNetworkRecoveries += 1;
                recordAction('HLS network recovery with startLoad', reason);
                notify('onReconnecting', t('Reconnecting to the live stream...'));
                hls.startLoad();
                return;
            }
            if (data.type === Hls.ErrorTypes.MEDIA_ERROR && hlsMediaRecoveries < 3) {
                hlsMediaRecoveries += 1;
                recordAction('HLS media recovery', reason);
                notify('onReconnecting', t('Repairing video playback...'));
                hls.recoverMediaError();
                return;
            }
            if (
                data.type === Hls.ErrorTypes.NETWORK_ERROR
                && extractHttpStatus(data) === null
                && isCrossOriginDirectSource()
            ) {
                finalFailureMessage = t('This source works in external players but the browser blocked it. Use an authorized proxy or HLS-compatible source.');
                notify('onBrowserBlocked', finalFailureMessage);
            }
            if (tryUnknownEngineFallback(reason)) return;
            scheduleReconnect(reason, true);
        });
    };

    const loadMpegTs = () => {
        engineKind = 'mpegts';
        const featureList = mpegts?.getFeatureList?.();
        if (featureList && featureList.mseLivePlayback === false) {
            fatal(t('This browser does not support this stream. Try another server or external player.'));
            return;
        }

        mpegPlayer = mpegts.createPlayer({
            type: 'mpegts',
            isLive: true,
            url: finalStreamUrl,
        }, {
            enableWorker: true,
            enableStashBuffer: true,
            stashInitialSize: 1024 * 1024,
            lazyLoad: false,
            lazyLoadMaxDuration: 3 * 60,
            lazyLoadRecoverDuration: 30,
            deferLoadAfterSourceOpen: false,
            autoCleanupSourceBuffer: true,
            autoCleanupMaxBackwardDuration: 60,
            autoCleanupMinBackwardDuration: 30,
            fixAudioTimestampGap: true,
            accurateSeek: false,
        });
        mpegPlayer.attachMediaElement(video);
        mpegPlayer.on(mpegts.Events.ERROR, (...details) => {
            debug('MPEG-TS playback error.', { details });
            if (handleForbidden(details)) return;
            fatalErrorSeen = true;
            const errorKind = String(details[0] || details[1] || '').toLowerCase();
            const isMediaFormatError = errorKind.includes('media') || errorKind.includes('format');
            if (isMediaFormatError) {
                fatal(t('This browser does not support this stream. Try another server or external player.'));
                return;
            }
            if (extractHttpStatus(details) === null && isCrossOriginDirectSource()) {
                finalFailureMessage = t('This source works in external players but the browser blocked it. Use an authorized proxy or HLS-compatible source.');
                notify('onBrowserBlocked', finalFailureMessage);
            }
            scheduleReconnect('MPEG-TS playback error', true);
        });
        [
            'LOADING_COMPLETE',
            'RECOVERED_EARLY_EOF',
            'MEDIA_INFO',
            'STATISTICS_INFO',
        ].forEach((eventName) => {
            if (mpegts.Events[eventName]) {
                mpegPlayer.on(mpegts.Events[eventName], (...details) => {
                    debug(`MPEG-TS ${eventName}.`, { details });
                    publishDebug();
                });
            }
        });
        mpegPlayer.load();
        safePlay();
    };

    function load() {
        if (destroyed) return;
        cleanupEngine();
        resetMedia();
        lastProgressAt = Date.now();
        lastCurrentTime = 0;
        autoplayBlocked = false;
        notify('onLoading');

        if (isMixedContent()) {
            fatalErrorSeen = true;
            recordAction('mixed content blocked', 'HTTP source on HTTPS page');
            notify('onMixedContent', t('HTTP stream cannot be played directly on an HTTPS page. Use an HTTPS source or authorized secure proxy.'));
            fatal(t('HTTP stream cannot be played directly on an HTTPS page. Use an HTTPS source or authorized secure proxy.'));
            return;
        }

        const type = currentEngineType();
        if ((type === 'hls' || type === 'auto') && Hls?.isSupported()) {
            loadHls();
            publishDebug();
            return;
        }

        if ((type === 'hls' || type === 'auto') && video.canPlayType('application/vnd.apple.mpegurl')) {
            engineKind = 'native';
            video.src = finalStreamUrl;
            video.load();
            safePlay();
            publishDebug();
            return;
        }

        if ((type === 'mpegts' || type === 'auto') && mpegts?.isSupported()) {
            loadMpegTs();
            publishDebug();
            return;
        }

        engineKind = 'native';
        video.src = finalStreamUrl;
        video.load();
        safePlay();
        publishDebug();
    }

    function scheduleReconnect(reason, fatalError = false, immediate = false) {
        if (destroyed || retryTimer || finalErrorShown) return;
        if (!navigator.onLine) {
            recordAction('waiting for network connection', reason);
            notify('onReconnecting', t('No connection'));
            return;
        }

        fatalErrorSeen ||= fatalError;
        lastError = reason;
        if (retryCount >= maxReconnects) {
            finalizeWhenProven();
            return;
        }

        const delay = immediate ? 0 : RETRY_BASE_DELAY_MS * (2 ** retryCount);
        retryCount += 1;
        notify('onReconnecting', t('Reconnecting... attempt :attempt of :total', {
            attempt: retryCount,
            total: maxReconnects,
        }));
        recordAction(`reconnecting source in ${delay} ms`, reason);
        clearTimeout(stableTimer);
        cleanupEngine();
        retryTimer = window.setTimeout(() => {
            retryTimer = null;
            load();
        }, delay);
    }

    const markProgress = () => {
        const bufferedAhead = getBufferedAhead(video);
        if (
            video.currentTime > lastCurrentTime + 0.05
            || bufferedAhead >= MIN_HEALTHY_BUFFER_SECONDS
            || video.readyState >= HTMLMediaElement.HAVE_FUTURE_DATA
        ) {
            lastCurrentTime = video.currentTime;
            lastProgressAt = Date.now();
        }
        publishDebug();
    };

    const markPlayable = (action) => {
        lastProgressAt = Date.now();
        recordAction(action);
    };

    addListener(video, 'playing', () => {
        autoplayBlocked = false;
        finalErrorShown = false;
        markPlayable('playing');
        notify('onPlaying');
        clearTimeout(stableTimer);
        stableTimer = window.setTimeout(() => {
            retryCount = 0;
            hlsNetworkRecoveries = 0;
            hlsMediaRecoveries = 0;
            fatalErrorSeen = false;
            lastError = null;
            recordAction('stable playback');
        }, 30000);
    });
    addListener(video, 'canplay', () => {
        markPlayable('can play');
        notify('onCanPlay');
    });
    addListener(video, 'loadeddata', () => markPlayable('media data loaded'));
    addListener(video, 'progress', markProgress);
    addListener(video, 'timeupdate', markProgress);
    addListener(video, 'waiting', () => {
        recordAction('buffering');
        notify('onReconnecting', t('The live stream is buffering...'));
    });
    addListener(video, 'stalled', () => {
        recordAction('browser reported stalled media');
        if (isReallyStalled()) scheduleReconnect('confirmed stalled event', true);
    });
    addListener(video, 'suspend', () => debug('Browser suspended media loading.'));
    addListener(video, 'emptied', () => debug('Media buffer emptied.'));
    addListener(video, 'pause', publishDebug);
    addListener(video, 'ended', () => {
        fatalErrorSeen = true;
        if (!document.hidden) scheduleReconnect('stream ended', true);
    });
    addListener(video, 'error', () => {
        fatalErrorSeen = true;
        scheduleReconnect(`media error ${video.error?.code || 'unknown'}`, true);
    });
    addListener(window, 'offline', () => {
        clearTimeout(retryTimer);
        retryTimer = null;
        recordAction('offline');
        notify('onReconnecting', t('No connection'));
    });
    addListener(window, 'online', () => scheduleReconnect('connection restored', false, true));
    addListener(document, 'visibilitychange', () => {
        if (!document.hidden && isReallyStalled()) scheduleReconnect('page became visible', true);
    });

    healthTimer = window.setInterval(() => {
        if (destroyed || autoplayBlocked || document.hidden || video.paused || video.ended) return;
        markProgress();
        if (isReallyStalled()) scheduleReconnect('playback health timeout', true);
    }, 5000);

    load();

    const controller = {
        reconnect(reason = 'manual retry') {
            retryCount = 0;
            fatalErrorSeen = false;
            finalErrorShown = false;
            scheduleReconnect(reason, false, true);
        },
        play() {
            safePlay();
        },
        debugState,
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
            clearTimeout(finalCheckTimer);
            clearInterval(healthTimer);
            retryTimer = null;
            stableTimer = null;
            finalCheckTimer = null;
            healthTimer = null;
            listeners.splice(0).forEach((remove) => remove());
            cleanupEngine();
            resetMedia();
            if (activePlayer === controller) activePlayer = null;
        },
    };

    activePlayer = controller;
    publishDebug();

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

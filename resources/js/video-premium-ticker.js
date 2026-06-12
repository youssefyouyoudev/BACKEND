const DEFAULT_INTERVAL_MS = 30 * 60 * 1000;
const DEFAULT_DURATION_MS = 20 * 1000;

const positiveNumber = (value, fallback) => {
    const number = Number(value);

    return Number.isFinite(number) && number > 0 ? number : fallback;
};

const setupTicker = (ticker) => {
    if (ticker.dataset.premiumTickerReady === '1') return;

    ticker.dataset.premiumTickerReady = '1';

    const intervalMs = positiveNumber(ticker.dataset.intervalMs, DEFAULT_INTERVAL_MS);
    const durationMs = positiveNumber(ticker.dataset.durationMs, DEFAULT_DURATION_MS);
    let firstShowTimer = null;
    let repeatTimer = null;
    let hideTimer = null;
    let destroyed = false;

    const hide = () => {
        if (destroyed) return;
        ticker.classList.remove('is-visible');
        ticker.setAttribute('aria-hidden', 'true');
        ticker.tabIndex = -1;
    };

    const show = () => {
        if (destroyed) return;
        if (!ticker.isConnected) {
            cleanup();
            return;
        }

        clearTimeout(hideTimer);
        ticker.classList.add('is-visible');
        ticker.setAttribute('aria-hidden', 'false');
        ticker.tabIndex = 0;
        hideTimer = window.setTimeout(hide, durationMs);
    };

    const cleanup = () => {
        if (destroyed) return;
        destroyed = true;
        clearTimeout(firstShowTimer);
        clearTimeout(hideTimer);
        clearInterval(repeatTimer);
        window.removeEventListener('pagehide', cleanup);
        document.removeEventListener('turbo:before-cache', cleanup);
        ticker.dataset.premiumTickerReady = '0';
    };

    firstShowTimer = window.setTimeout(() => {
        show();
        repeatTimer = window.setInterval(show, intervalMs);
    }, intervalMs);

    window.addEventListener('pagehide', cleanup, { once: true });
    document.addEventListener('turbo:before-cache', cleanup, { once: true });
};

export const initVideoPremiumTickers = () => {
    document.querySelectorAll('[data-premium-video-ticker]').forEach(setupTicker);
};

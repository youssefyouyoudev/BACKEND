import { t } from './i18n';

const pad = (value) => String(Math.max(0, value)).padStart(2, '0');
const countdownTimers = new WeakMap();

export function initCountdown(containerSelector, targetDate = null) {
    const element = typeof containerSelector === 'string'
        ? document.querySelector(containerSelector)
        : containerSelector;
    targetDate ??= element?.dataset.countdownTarget;

    if (!(element instanceof HTMLElement)) {
        return () => {};
    }

    const existingTimer = countdownTimers.get(element);
    if (existingTimer) window.clearInterval(existingTimer);

    const target = new Date(targetDate).getTime();
    if (!Number.isFinite(target)) return () => {};

    const fields = {
        days: element.querySelector('[data-countdown-days]'),
        hours: element.querySelector('[data-countdown-hours]'),
        minutes: element.querySelector('[data-countdown-minutes]'),
        seconds: element.querySelector('[data-countdown-seconds]'),
    };

    let timer = null;
    const update = () => {
        const remaining = Math.max(0, target - Date.now());
        const totalSeconds = Math.floor(remaining / 1000);
        const values = {
            days: Math.floor(totalSeconds / 86400),
            hours: Math.floor((totalSeconds % 86400) / 3600),
            minutes: Math.floor((totalSeconds % 3600) / 60),
            seconds: totalSeconds % 60,
        };

        Object.entries(values).forEach(([name, value]) => {
            if (fields[name]) fields[name].textContent = pad(value);
        });

        if (remaining > 0) return;
        window.clearInterval(timer);
        countdownTimers.delete(element);
        timer = null;
        const status = element.querySelector('[data-countdown-status]');
        if (status) status.textContent = t('Starting now');
    };

    update();
    timer = window.setInterval(update, 1000);
    countdownTimers.set(element, timer);

    return () => {
        window.clearInterval(timer);
        countdownTimers.delete(element);
    };
}

export function initCountdowns(root = document) {
    return [...root.querySelectorAll('[data-countdown]')]
        .map((element) => initCountdown(element));
}

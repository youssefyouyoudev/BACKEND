const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

export function initCountdown(containerSelector, targetDate = null) {
    const element = typeof containerSelector === 'string'
        ? document.querySelector(containerSelector)
        : containerSelector;
    targetDate ??= element?.dataset.countdownTarget;

    if (!(element instanceof HTMLElement) || element.dataset.countdownReady === 'true') {
        return () => {};
    }

    const target = new Date(targetDate).getTime();
    if (!Number.isFinite(target)) return () => {};

    element.dataset.countdownReady = 'true';
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
        timer = null;
        const status = element.querySelector('[data-countdown-status]');
        if (status) status.textContent = 'Starting now / ينطلق الآن';
    };

    update();
    timer = window.setInterval(update, 1000);

    return () => {
        window.clearInterval(timer);
        element.dataset.countdownReady = 'false';
    };
}

export function initCountdowns(root = document) {
    return [...root.querySelectorAll('[data-countdown]')]
        .map((element) => initCountdown(element));
}

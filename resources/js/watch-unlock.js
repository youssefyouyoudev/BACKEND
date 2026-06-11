export const initWatchUnlocks = () => {
    const unlocks = [...document.querySelectorAll('[data-watch-unlock-at]')]
        .map((element) => Date.parse(element.dataset.watchUnlockAt))
        .filter(Number.isFinite);

    if (unlocks.length === 0) return;

    const nextUnlock = Math.min(...unlocks);
    const refreshWhenReady = () => {
        const remaining = nextUnlock - Date.now();

        if (remaining <= 0) {
            window.location.reload();
            return;
        }

        window.setTimeout(refreshWhenReady, Math.min(remaining, 60_000));
    };

    refreshWhenReady();
};

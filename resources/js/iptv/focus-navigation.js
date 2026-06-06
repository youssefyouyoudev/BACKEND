export function initFocusNavigation() {
    const focusables = () => Array.from(document.querySelectorAll('[data-focusable]'))
        .filter((element) => !element.hidden && element.offsetParent !== null);

    document.addEventListener('keydown', (event) => {
        if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(event.key)) return;

        const items = focusables();
        if (items.length === 0) return;

        const currentIndex = Math.max(0, items.indexOf(document.activeElement));
        const columns = event.key === 'ArrowLeft' || event.key === 'ArrowRight' ? 1 : 4;
        const direction = event.key === 'ArrowLeft' || event.key === 'ArrowUp' ? -1 : 1;

        if (event.key === 'Enter' && document.activeElement?.matches('[data-focusable]')) {
            document.activeElement.click();
            return;
        }

        event.preventDefault();
        const next = items[Math.min(items.length - 1, Math.max(0, currentIndex + direction * columns))];
        next?.focus();
    });
}

const CLOSED_UNTIL_KEY = 'sticky_ad_closed_until';

function isClosed() {
    return Date.now() < Number(localStorage.getItem(CLOSED_UNTIL_KEY) || 0);
}

function markClosed() {
    localStorage.setItem(CLOSED_UNTIL_KEY, String(Date.now() + 12 * 60 * 60 * 1000));
    window.dispatchEvent(new CustomEvent('rifitv:ad-event', {
        detail: { name: 'sticky_ad_closed' },
    }));
}

export function mountStickyMobileAd(config = {}) {
    const slot = document.querySelector('[data-ad-slot="sticky_mobile"]');
    if (!slot || !config?.placements?.stickyMobile || isClosed()) {
        slot?.remove();
        return;
    }

    document.body.classList.add('rifitv-has-sticky-ad');

    slot.addEventListener('click', (event) => {
        if (!event.target.closest('[data-ad-dismiss]')) {
            return;
        }

        event.preventDefault();
        markClosed();
        slot.remove();
        document.body.classList.remove('rifitv-has-sticky-ad');
    });
}

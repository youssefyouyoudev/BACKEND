export function observeAdSlots() {
    const slots = document.querySelectorAll('[data-ad-slot]');
    if (!slots.length || !('IntersectionObserver' in window)) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            window.dispatchEvent(new CustomEvent('rifitv:ad-event', {
                detail: {
                    name: 'ad_slot_visible',
                    placement: entry.target.dataset.adSlot,
                },
            }));
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '120px' });

    slots.forEach((slot) => observer.observe(slot));
}

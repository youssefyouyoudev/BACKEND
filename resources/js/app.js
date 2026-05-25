import './bootstrap';
import Alpine from 'alpinejs';
import mpegts from 'mpegts.js';

window.Alpine = Alpine;
window.mpegts = mpegts;

const applyThemeLabel = () => {
    const isLight = document.documentElement.classList.contains('theme-light');
    document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.textContent = isLight ? 'D' : 'L';
    });
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', 'Switch theme');
        button.setAttribute('title', 'Switch theme');
    });
};

const setTheme = (theme) => {
    const normalized = theme === 'light' ? 'light' : 'dark';
    const root = document.documentElement;
    root.classList.toggle('theme-light', normalized === 'light');
    root.classList.toggle('theme-dark', normalized === 'dark');
    root.classList.toggle('light', normalized === 'light');
    root.classList.toggle('dark', normalized === 'dark');
    root.dataset.theme = normalized;
    root.style.colorScheme = normalized;
    localStorage.setItem('rifi-theme', normalized);
    applyThemeLabel();
};

document.addEventListener('DOMContentLoaded', () => {
    const stored = localStorage.getItem('rifi-theme');
    if (! stored) {
        setTheme(window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    }
    applyThemeLabel();

    const navbar = document.querySelector('[data-navbar]');
    const syncNavbar = () => {
        if (! navbar) return;
        navbar.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    syncNavbar();
    window.addEventListener('scroll', syncNavbar, { passive: true });

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = document.documentElement.classList.contains('theme-light') ? 'dark' : 'light';
            button.classList.remove('is-bouncing');
            void button.offsetWidth;
            button.classList.add('is-bouncing');
            setTheme(next);
        });
    });

    document.addEventListener('error', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLImageElement)) return;

        const fallback = target.dataset.fallbackSrc || '/brand/rifi-logo.png';
        if (target.src.endsWith(fallback)) return;
        target.src = fallback;
    }, true);

    const revealItems = document.querySelectorAll('[data-reveal], .rm-section, .rm-match-card, .football-match-card, .rm-story-card, .rm-directory-card');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

        revealItems.forEach((item) => {
            item.classList.add('rm-reveal');
            observer.observe(item);
        });
    } else {
        revealItems.forEach((item) => item.classList.add('is-revealed'));
    }

    document.querySelectorAll('.rm-carousel-shell').forEach((shell) => {
        const track = shell.querySelector('[data-carousel]');
        if (!track) return;

        shell.addEventListener('click', (event) => {
            const button = event.target.closest('[data-carousel-prev], [data-carousel-next]');
            if (!button) return;

            const direction = button.hasAttribute('data-carousel-prev') ? -1 : 1;
            track.scrollBy({ left: direction * Math.max(260, track.clientWidth * 0.78), behavior: 'smooth' });
        });
    });
});

Alpine.start();

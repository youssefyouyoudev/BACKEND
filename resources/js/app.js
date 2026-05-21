import './bootstrap';
import Alpine from 'alpinejs';
import mpegts from 'mpegts.js';

window.Alpine = Alpine;
window.mpegts = mpegts;

const applyThemeLabel = () => {
    const isLight = document.documentElement.classList.contains('theme-light');
    document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.textContent = isLight ? '☾' : '☀';
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
});

Alpine.start();

import './bootstrap';
import Alpine from 'alpinejs';
import mpegts from 'mpegts.js';

window.Alpine = Alpine;
window.mpegts = mpegts;

const applyThemeLabel = () => {
    const isLight = document.documentElement.classList.contains('theme-light');
    document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.textContent = isLight ? 'Dark' : 'Light';
    });
};

document.addEventListener('DOMContentLoaded', () => {
    applyThemeLabel();

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const root = document.documentElement;
            const next = root.classList.contains('theme-light') ? 'dark' : 'light';
            root.classList.toggle('theme-light', next === 'light');
            root.classList.toggle('theme-dark', next === 'dark');
            localStorage.setItem('rifi-theme', next);
            applyThemeLabel();
        });
    });
});

Alpine.start();

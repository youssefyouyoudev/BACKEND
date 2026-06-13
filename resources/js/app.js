import './bootstrap';
import Alpine from 'alpinejs';
import Hls from 'hls.js';
import mpegts from 'mpegts.js';
import { initFocusNavigation } from './iptv/focus-navigation';
import { initIptvPlayer } from './iptv/player';
import { initIptvSearch } from './iptv/search';
import { initPlaylistForms } from './iptv/playlist-form';
import { initAdminIptvItems } from './iptv/admin-items';
import { groupChannelVariants } from './iptv/live-tv';
import { initCountdowns } from './countdown';
import { initWorldCupAdmin } from './world-cup-admin';
import { initWatchUnlocks } from './watch-unlock';
import { initVideoPremiumTickers } from './video-premium-ticker';
import { initMatchPlayers } from './match-player';
import { t } from './i18n';

const earlyRifiT = window.rifiT;

window.rifiTranslations = window.rifiTranslations || {};
window.rifiT = function (key, fallbackOrReplacements = null) {
    const replacements = fallbackOrReplacements && typeof fallbackOrReplacements === 'object'
        ? fallbackOrReplacements
        : {};
    const translated = t(key, replacements);

    if (translated !== key) return translated;

    return earlyRifiT?.(key, fallbackOrReplacements)
        || (typeof fallbackOrReplacements === 'string' ? fallbackOrReplacements : translated);
};
window.Alpine = Alpine;
window.Hls = Hls;
window.mpegts = mpegts;

const THEME_STORAGE_KEY = 'rifitv-theme';
const LEGACY_THEME_STORAGE_KEY = 'rifi-theme';

const currentTheme = () => document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';

const applyThemeLabel = () => {
    const isLight = currentTheme() === 'light';
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = isLight ? t('Switch to dark mode') : t('Switch to light mode');
        button.dataset.themeState = isLight ? 'light' : 'dark';
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', isLight ? 'false' : 'true');
    });
};

const setTheme = (theme, persist = true) => {
    const normalized = theme === 'light' ? 'light' : 'dark';
    const root = document.documentElement;
    root.classList.toggle('theme-light', normalized === 'light');
    root.classList.toggle('theme-dark', normalized === 'dark');
    root.classList.toggle('light', normalized === 'light');
    root.classList.toggle('dark', normalized === 'dark');
    root.dataset.theme = normalized;
    root.style.colorScheme = normalized;
    if (persist) {
        localStorage.setItem(THEME_STORAGE_KEY, normalized);
        localStorage.removeItem(LEGACY_THEME_STORAGE_KEY);
    }
    applyThemeLabel();
    window.dispatchEvent(new CustomEvent('rifitv:theme-changed', {
        detail: { theme: normalized },
    }));
};

window.rifiTheme = {
    get: currentTheme,
    set: (theme) => setTheme(theme, true),
    toggle: () => setTheme(currentTheme() === 'light' ? 'dark' : 'light', true),
};

document.addEventListener('DOMContentLoaded', () => {
    initCountdowns();
    initVideoPremiumTickers();
    initMatchPlayers();

    document.querySelectorAll('[data-channel-picker]').forEach((picker) => {
        const search = picker.querySelector('[data-channel-search]');
        const select = picker.querySelector('[data-channel-select]');
        const preview = picker.querySelector('[data-channel-preview]');
        if (! search || ! select) return;

        const options = [...select.options];
        const renderPreview = () => {
            if (! preview) return;
            const option = select.selectedOptions[0];
            if (! option?.value) {
                preview.replaceChildren();
                return;
            }

            const image = document.createElement('img');
            image.src = option.dataset.logo || '/brand/rifi-logo.png';
            image.alt = '';
            image.dataset.fallbackSrc = '/brand/rifi-logo.png';

            const content = document.createElement('span');
            const name = document.createElement('strong');
            name.textContent = option.textContent.trim();
            content.append(name);
            preview.replaceChildren(image, content);
        };

        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            options.forEach((option) => {
                option.hidden = Boolean(query) && ! (option.dataset.search || option.textContent.toLowerCase()).includes(query);
            });
        });
        select.addEventListener('change', renderPreview);
        renderPreview();
    });

    document.querySelectorAll('[data-local-select-search]').forEach((search) => {
        const select = document.getElementById(search.dataset.localSelectSearch);
        if (!select) return;

        const options = [...select.options];
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            options.forEach((option) => {
                option.hidden = Boolean(query) && ! (option.dataset.search || option.textContent.toLowerCase()).includes(query);
            });
        });
    });

    document.querySelectorAll('[data-match-iptv-repeater]').forEach((repeater) => {
        const rows = repeater.querySelector('[data-match-iptv-rows]');
        const template = repeater.querySelector('[data-match-iptv-template]');
        const addButton = document.querySelector('[data-add-match-iptv-row]');
        if (!rows || !template || !addButton) return;

        const bindRowSearch = (row) => {
            row.querySelectorAll('[data-local-select-search]').forEach((search) => {
                const select = document.getElementById(search.dataset.localSelectSearch);
                if (!select) return;

                const options = [...select.options];
                search.addEventListener('input', () => {
                    const query = search.value.trim().toLowerCase();
                    options.forEach((option) => {
                        option.hidden = Boolean(query) && ! (option.dataset.search || option.textContent.toLowerCase()).includes(query);
                    });
                });
            });
        };

        rows.querySelectorAll('[data-match-iptv-row]').forEach(bindRowSearch);
        repeater.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-match-iptv-row]');
            if (!remove) return;

            remove.closest('[data-match-iptv-row]')?.remove();
            if (!rows.querySelector('[data-match-iptv-row]')) {
                const empty = document.createElement('p');
                empty.className = 'empty-state';
                empty.textContent = t('No match servers attached yet.');
                rows.append(empty);
            }
        });

        addButton.addEventListener('click', () => {
            rows.querySelector('.empty-state')?.remove();
            const index = Date.now();
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            const priority = row.querySelector('input[name$="[priority]"]');
            if (priority && !priority.value) {
                priority.value = String(rows.querySelectorAll('[data-match-iptv-row]').length + 1);
            }
            rows.append(row);
            bindRowSearch(row);
        });
    });

    document.querySelectorAll('[data-live-channel-count]').forEach((element) => {
        try {
            const cached = JSON.parse(localStorage.getItem('rifi-live-tv-catalog-v4'));
            const groupedCount = groupChannelVariants(cached?.channels || []).length;
            const serverCount = Number(String(element.textContent || '').replace(/[^\d]/g, '')) || 0;
            if (groupedCount > 0 && (serverCount === 0 || groupedCount !== serverCount)) {
                element.textContent = groupedCount.toLocaleString();
            }
        } catch (error) {
            console.debug('[RifiLiveTV] Saved homepage count is unavailable.', error);
        }
    });

    const stored = localStorage.getItem(THEME_STORAGE_KEY) || localStorage.getItem(LEGACY_THEME_STORAGE_KEY);
    if (! stored) {
        setTheme(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light', false);
    } else if (! localStorage.getItem(THEME_STORAGE_KEY)) {
        localStorage.setItem(THEME_STORAGE_KEY, stored);
        localStorage.removeItem(LEGACY_THEME_STORAGE_KEY);
    }
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
        if (localStorage.getItem(THEME_STORAGE_KEY)) return;
        setTheme(event.matches ? 'dark' : 'light', false);
    });
    applyThemeLabel();

    const navbar = document.querySelector('[data-navbar]');
    let lastScrollY = window.scrollY;
    const syncNavbar = () => {
        if (! navbar) return;
        const currentScrollY = window.scrollY;
        navbar.classList.toggle('is-scrolled', currentScrollY > 12);
        navbar.classList.toggle('is-condensed', currentScrollY > 96);
        navbar.classList.toggle('is-rising', currentScrollY < lastScrollY && currentScrollY > 96);
        lastScrollY = currentScrollY;
    };
    syncNavbar();
    window.addEventListener('scroll', syncNavbar, { passive: true });

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = currentTheme() === 'light' ? 'dark' : 'light';
            button.classList.remove('is-bouncing');
            void button.offsetWidth;
            button.classList.add('is-bouncing');
            setTheme(next, true);
        });
    });

    document.querySelectorAll('[data-ad-dismiss]').forEach((button) => {
        const slot = button.closest('[data-ad-slot]');
        const storageKey = `rifi-ad-dismissed:${slot?.dataset.adSlot || 'sticky'}`;

        if (sessionStorage.getItem(storageKey) === '1') {
            slot?.remove();
            return;
        }

        button.addEventListener('click', () => {
            sessionStorage.setItem(storageKey, '1');
            slot?.remove();
        });
    });

    initPlaylistForms();
    initAdminIptvItems();
    initWorldCupAdmin();
    initWatchUnlocks();
    initFocusNavigation();
    initIptvPlayer();
    initIptvSearch();

    document.addEventListener('error', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLImageElement)) return;

        const fallback = target.dataset.fallbackSrc || '/brand/rifi-logo.png';
        if (target.src.endsWith(fallback)) return;
        target.src = fallback;
    }, true);

    const revealItems = document.querySelectorAll('[data-reveal], .rm-section, .rm-match-card, .football-match-card, .rm-story-card, .rm-directory-card, .rm-media-card');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if ('IntersectionObserver' in window && !reducedMotion) {
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
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        shell.addEventListener('click', (event) => {
            const button = event.target.closest('[data-carousel-prev], [data-carousel-next]');
            if (!button) return;

            const direction = button.hasAttribute('data-carousel-prev') ? -1 : 1;
            track.scrollBy({
                left: direction * Math.max(260, track.clientWidth * 0.78),
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
            });
        });
    });
});

Alpine.start();

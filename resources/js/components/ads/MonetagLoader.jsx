const HEAVY_AD_STORAGE_KEY = 'monetag_heavy_ad_last_seen';
const SESSION_LOADED_KEY = 'monetag_session_loaded';

export const DEFAULT_MONETAG_SCRIPTS = [
    {
        id: 'monetag-zone-11137947',
        zone: '11137947',
        src: 'https://al5sm.com/tag.min.js',
        enabled: true,
        heavy: false,
    },
    {
        id: 'monetag-zone-11137952',
        zone: '11137952',
        src: 'https://nap5k.com/tag.min.js',
        enabled: true,
        heavy: false,
    },
    {
        id: 'monetag-vignette-11137954',
        zone: '11137954',
        src: 'https://n6wxm.com/vignette.min.js',
        enabled: true,
        heavy: true,
        delayMs: 30000,
    },
];

export function loadAdScript({ id, zone, src }) {
    if (!id || !zone || !src || document.getElementById(id)) {
        return null;
    }

    const script = document.createElement('script');
    script.id = id;
    script.dataset.zone = String(zone);
    script.src = src;
    script.async = true;
    script.defer = true;

    script.addEventListener('load', () => {
        window.dispatchEvent(new CustomEvent('rifitv:ad-event', {
            detail: { name: 'monetag_script_loaded', id, zone },
        }));
    }, { once: true });

    document.body.appendChild(script);

    return script;
}

export function canLoadHeavyAd() {
    const lastSeen = Number(localStorage.getItem(HEAVY_AD_STORAGE_KEY) || 0);
    const alreadyLoaded = sessionStorage.getItem(SESSION_LOADED_KEY) === '1';

    return !alreadyLoaded && Date.now() - lastSeen >= 30000;
}

export function markHeavyAdLoaded() {
    localStorage.setItem(HEAVY_AD_STORAGE_KEY, String(Date.now()));
    sessionStorage.setItem(SESSION_LOADED_KEY, '1');
}

export function loadMonetagScripts(config = {}) {
    if (window.__RIFITV_ADS_LOADED) {
        return;
    }

    const configured = Array.isArray(config.monetag) ? config.monetag : [];
    const configuredById = new Map(configured.map((script) => [script.id, script]));
    const scripts = DEFAULT_MONETAG_SCRIPTS.map((script) => ({
        ...script,
        ...(configuredById.get(script.id) || {}),
    }));

    scripts.forEach((scriptConfig) => {
        if (!scriptConfig?.enabled) {
            return;
        }

        if (scriptConfig.heavy && !canLoadHeavyAd()) {
            return;
        }

        const load = () => {
            const script = loadAdScript(scriptConfig);
            if (scriptConfig.heavy && script) {
                markHeavyAdLoaded();
            }
        };

        if (scriptConfig.heavy) {
            window.setTimeout(load, Number(scriptConfig.delayMs || 30000));
            return;
        }

        load();
    });

    window.__RIFITV_ADS_LOADED = true;
}

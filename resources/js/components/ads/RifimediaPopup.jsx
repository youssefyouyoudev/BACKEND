const POPUP_STORAGE_KEY = 'rifimedia_popup_last_seen';

function isFullscreenActive() {
    return Boolean(document.fullscreenElement || document.webkitFullscreenElement);
}

function track(name) {
    window.dispatchEvent(new CustomEvent('rifitv:ad-event', { detail: { name } }));
}

function shouldShowPopup(config) {
    if (!config?.enabled || isFullscreenActive()) {
        return false;
    }

    const lastSeen = Number(localStorage.getItem(POPUP_STORAGE_KEY) || 0);
    const frequencyMs = Number(config.frequencyHours || 24) * 60 * 60 * 1000;

    return Date.now() - lastSeen >= frequencyMs;
}

export function showRifimediaPopup(config = {}, onDone = () => {}) {
    if (!shouldShowPopup(config) || document.getElementById('rifimedia-popup')) {
        onDone();
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'rifimedia-popup';
    overlay.className = 'rifimedia-popup';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = `
        <div class="rifimedia-popup__card">
            <button type="button" class="rifimedia-popup__close" data-rifimedia-close aria-label="Close">&times;</button>
            <span class="rifimedia-popup__eyebrow">Premium</span>
            <h2></h2>
            <p></p>
            <div class="rifimedia-popup__actions">
                <a class="rifimedia-popup__primary" target="_blank" rel="noopener sponsored nofollow noreferrer"></a>
                <button type="button" class="rifimedia-popup__secondary" data-rifimedia-close></button>
            </div>
        </div>
    `;

    overlay.querySelector('h2').textContent = config.title || 'RifiMedia Premium';
    overlay.querySelector('p').textContent = config.message || '';
    const primary = overlay.querySelector('a');
    primary.href = config.url || 'https://rifimedia.com';
    primary.textContent = 'زيارة RifiMedia';
    overlay.querySelector('.rifimedia-popup__secondary').textContent = 'نكمل فـ RifiTV';

    const close = (eventName) => {
        localStorage.setItem(POPUP_STORAGE_KEY, String(Date.now()));
        overlay.remove();
        track(eventName);
        onDone();
    };

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay || event.target.closest('[data-rifimedia-close]')) {
            close('rifimedia_popup_closed');
        }
    });

    primary.addEventListener('click', () => {
        localStorage.setItem(POPUP_STORAGE_KEY, String(Date.now()));
        track('rifimedia_popup_clicked');
        onDone();
    }, { once: true });

    document.body.appendChild(overlay);
    localStorage.setItem(POPUP_STORAGE_KEY, String(Date.now()));
    track('rifimedia_popup_shown');
}

export function scheduleRifimediaPopup(config = {}, onDone = () => {}) {
    const delay = 6000 + Math.floor(Math.random() * 4000);
    window.setTimeout(() => showRifimediaPopup(config, onDone), delay);
}

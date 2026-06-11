import { choice, t } from './i18n';

const jsonHeaders = (csrf) => ({
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrf,
});

const resultButton = (item, selectedIds = []) => {
    const isSelected = selectedIds.includes(Number(item.id));
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `wc-iptv-result${isSelected ? ' is-selected' : ''}`;
    button.dataset.iptvResult = String(item.id);
    button.setAttribute('aria-pressed', String(isSelected));

    const logo = document.createElement('img');
    logo.src = item.logo || '/brand/rifi-logo.png';
    logo.alt = '';
    logo.dataset.fallbackSrc = '/brand/rifi-logo.png';

    const copy = document.createElement('span');
    const name = document.createElement('strong');
    const meta = document.createElement('small');
    name.textContent = item.name;
    meta.textContent = `${item.category} · ${item.quality}`;
    copy.append(name, meta);

    const check = document.createElement('b');
    check.className = 'wc-iptv-result__check';
    check.textContent = isSelected ? t('Selected') : t('Add');

    button.append(logo, copy, check);
    return button;
};

const selectedIdsFor = (card) => {
    try {
        return JSON.parse(card?.dataset.assignedIptvIds || '[]').map(Number);
    } catch {
        return [];
    }
};

export const initWorldCupAdmin = () => {
    const root = document.querySelector('[data-world-cup-admin]');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const searchEndpoint = root.dataset.iptvSearchEndpoint;
    let searchTimer;

    root.addEventListener('click', async (event) => {
        const toggle = event.target.closest('[data-iptv-picker-toggle]');
        if (toggle) {
            const card = toggle.closest('[data-match-card]');
            const picker = card?.querySelector('[data-iptv-picker]');
            if (!picker) return;

            const opening = picker.hidden;
            root.querySelectorAll('[data-iptv-picker]').forEach((element) => {
                if (element !== picker) element.hidden = true;
            });
            picker.hidden = !opening;
            toggle.setAttribute('aria-expanded', String(opening));

            if (opening) {
                const input = picker.querySelector('[data-iptv-search]');
                input?.focus();
                input?.dispatchEvent(new Event('input', { bubbles: true }));
            }
            return;
        }

        const result = event.target.closest('[data-iptv-result]');
        const clear = event.target.closest('[data-iptv-clear]');
        if (!result && !clear) return;

        const picker = event.target.closest('[data-iptv-picker]');
        const card = picker?.closest('[data-match-card]');
        if (!picker || !card) return;

        const itemId = result ? Number(result.dataset.iptvResult) : null;
        const status = picker.querySelector('[data-iptv-status]');
        picker.classList.add('is-saving');
        if (status) status.textContent = itemId ? t('Updating channels...') : t('Removing all channels...');

        try {
            const response = await fetch(card.dataset.assignIptvEndpoint, {
                method: 'PATCH',
                headers: jsonHeaders(csrf),
                body: JSON.stringify({ iptv_item_id: itemId }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || t('Unable to update this match.'));

            const assignments = payload.assignments || [];
            const selectedIds = assignments.map((assignment) => Number(assignment.id));
            card.dataset.assignedIptvIds = JSON.stringify(selectedIds);

            const channelName = card.querySelector('[data-assigned-iptv-name]');
            const availability = card.querySelector('[data-watch-availability]');
            if (channelName) {
                channelName.textContent = assignments.length
                    ? assignments.map((assignment) => assignment.name).join(' / ')
                    : t('No IPTV channel assigned');
            }
            if (availability) {
                availability.textContent = assignments.length
                    ? (payload.is_watch_window_open ? t('Watch links are available now') : availability.dataset.scheduledText)
                    : t('Choose one or more public IPTV channels');
            }
            if (status) status.textContent = payload.message;
            picker.querySelector('[data-iptv-clear]')?.toggleAttribute('hidden', assignments.length === 0);

            picker.querySelectorAll('[data-iptv-result]').forEach((button) => {
                const selected = selectedIds.includes(Number(button.dataset.iptvResult));
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', String(selected));
                const check = button.querySelector('.wc-iptv-result__check');
                if (check) check.textContent = selected ? t('Selected') : t('Add');
            });
        } catch (error) {
            if (status) status.textContent = error.message;
        } finally {
            picker.classList.remove('is-saving');
        }
    });

    root.addEventListener('input', (event) => {
        const input = event.target.closest('[data-iptv-search]');
        if (!input) return;

        clearTimeout(searchTimer);
        searchTimer = setTimeout(async () => {
            const picker = input.closest('[data-iptv-picker]');
            const card = picker?.closest('[data-match-card]');
            const results = picker?.querySelector('[data-iptv-results]');
            const status = picker?.querySelector('[data-iptv-status]');
            if (!picker || !results || !searchEndpoint) return;

            picker.classList.add('is-loading');
            if (status) status.textContent = t('Loading public channels...');

            try {
                const url = new URL(searchEndpoint, window.location.origin);
                url.searchParams.set('q', input.value.trim());
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || t('Unable to load IPTV channels.'));

                const selectedIds = selectedIdsFor(card);
                results.replaceChildren(...payload.items.map((item) => resultButton(item, selectedIds)));
                if (status) {
                    status.textContent = payload.items.length
                        ? choice(':count public channel found', ':count public channels found', payload.items.length)
                        : t('No public active channels match this search.');
                }
            } catch (error) {
                results.replaceChildren();
                if (status) status.textContent = error.message;
            } finally {
                picker.classList.remove('is-loading');
            }
        }, 250);
    });
};

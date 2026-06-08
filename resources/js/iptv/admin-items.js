const formatNumber = (value) => Number(value || 0).toLocaleString();

export function initAdminIptvItems() {
    const root = document.querySelector('[data-iptv-items-admin]');
    if (!root) return;

    const form = root.querySelector('[data-iptv-filter-form]');
    const rows = root.querySelector('[data-iptv-rows]');
    const pagination = root.querySelector('[data-iptv-pagination]');
    const status = root.querySelector('[data-catalog-status]');
    const feedback = root.querySelector('[data-iptv-feedback]');
    const search = form.querySelector('input[name="q"]');
    const bulkButtons = [...root.querySelectorAll('[data-bulk-visibility]')];
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let listController = null;
    let searchTimer = null;

    const showFeedback = (message, isError = false) => {
        feedback.textContent = message;
        feedback.hidden = false;
        feedback.classList.toggle('is-error', isError);
        window.clearTimeout(feedback.hideTimer);
        feedback.hideTimer = window.setTimeout(() => {
            feedback.hidden = true;
        }, 3600);
    };

    const updateSummary = (summary) => {
        Object.entries(summary || {}).forEach(([key, value]) => {
            document.querySelector(`[data-summary-${key}]`)?.replaceChildren(formatNumber(value));
        });
    };

    const currentUrl = () => {
        const params = new URLSearchParams(new FormData(form));
        [...params.entries()].forEach(([key, value]) => {
            if (!String(value).trim()) params.delete(key);
        });

        const query = params.toString();
        return `${root.dataset.endpoint}${query ? `?${query}` : ''}`;
    };

    const loadItems = async (url = currentUrl(), updateHistory = true) => {
        listController?.abort();
        const controller = new AbortController();
        listController = controller;
        root.classList.add('is-loading');
        status.textContent = 'Loading...';

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`Request failed with HTTP ${response.status}`);

            const payload = await response.json();
            rows.innerHTML = payload.rows;
            pagination.innerHTML = payload.pagination;
            updateSummary(payload.summary);
            status.textContent = `${formatNumber(payload.summary.filtered)} items`;

            if (updateHistory) {
                const browserUrl = new URL(url, window.location.origin);
                history.replaceState(null, '', `${browserUrl.pathname}${browserUrl.search}`);
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            status.textContent = 'Could not load';
            showFeedback('The IPTV item list could not be refreshed. Please try again.', true);
        } finally {
            if (listController === controller) {
                root.classList.remove('is-loading');
                listController = null;
            }
        }
    };

    const applyToggleState = (button, isPublic) => {
        const row = button.closest('[data-item-row]');
        button.dataset.isPublic = isPublic ? '1' : '0';
        button.setAttribute('aria-pressed', isPublic ? 'true' : 'false');
        button.classList.toggle('is-public', isPublic);
        button.querySelector('[data-toggle-label]').textContent = isPublic ? 'Public' : 'Hidden';
        row?.classList.toggle('is-hidden-publicly', !isPublic);
    };

    const setBulkLoading = (loading) => {
        bulkButtons.forEach((button) => {
            button.disabled = loading;
            button.classList.toggle('is-saving', loading);
        });
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadItems();
    });

    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => loadItems());
    });

    search.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadItems(), 350);
    });

    root.querySelector('[data-reset-filters]').addEventListener('click', () => {
        form.reset();
        loadItems(root.dataset.endpoint);
        search.focus();
    });

    pagination.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        event.preventDefault();
        loadItems(link.href);
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    bulkButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const isPublic = button.dataset.bulkVisibility === '1';
            const action = isPublic ? 'make every IPTV item public' : 'hide every IPTV item from the public website';

            if (!window.confirm(`Are you sure you want to ${action}?`)) return;

            setBulkLoading(true);
            status.textContent = isPublic ? 'Publishing all...' : 'Hiding all...';

            try {
                const response = await fetch(root.dataset.bulkVisibilityEndpoint, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ is_public: isPublic }),
                });
                if (!response.ok) throw new Error(`Request failed with HTTP ${response.status}`);

                const payload = await response.json();
                showFeedback(payload.message);
                await loadItems(currentUrl(), false);
            } catch {
                status.textContent = 'Bulk update failed';
                showFeedback('The catalog could not be updated. No display changes were applied.', true);
            } finally {
                setBulkLoading(false);
            }
        });
    });

    rows.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-visibility-toggle]');
        if (!button || button.disabled) return;

        const previous = button.dataset.isPublic === '1';
        const next = !previous;
        button.disabled = true;
        button.classList.add('is-saving');
        applyToggleState(button, next);

        try {
            const response = await fetch(button.dataset.url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ is_public: next }),
            });
            if (!response.ok) throw new Error(`Request failed with HTTP ${response.status}`);

            const payload = await response.json();
            applyToggleState(button, payload.item.is_public);
            showFeedback(payload.message);
            await loadItems(currentUrl(), false);
        } catch {
            applyToggleState(button, previous);
            showFeedback('Visibility could not be updated. The previous setting was restored.', true);
        } finally {
            button.disabled = false;
            button.classList.remove('is-saving');
        }
    });
}

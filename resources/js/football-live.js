const SELECTORS = {
    root: '[data-football-live]',
    eventRoot: '[data-football-event-page]',
    matches: '[data-football-matches]',
    status: '[data-football-status]',
};

const state = {
    root: null,
    activeFilter: 'today',
    activeCountry: 'All',
    refreshTimer: null,
    tvCache: new Map(),
};

export function initFootballLivescore() {
    state.root = document.querySelector(SELECTORS.root);

    if (state.root) {
        bindFootballPage();
        fetchTodayMatches();
        autoRefreshLiveMatches();
    }

    initFootballEventPage();
}

export async function fetchTodayMatches() {
    setActiveFilter('today');
    await loadMatches(state.root.dataset.todayUrl, 'No top league matches found for today.');
}

export async function fetchMatchesByDate(date) {
    setActiveFilter('date');
    const url = new URL(state.root.dataset.dateUrl, window.location.origin);
    url.searchParams.set('date', date);
    await loadMatches(url.toString(), 'No top league matches found for this date.');
}

export async function fetchUpcomingMatches() {
    setActiveFilter('upcoming');
    await loadMatches(state.root.dataset.upcomingUrl, 'Upcoming matches are not available yet.');
}

export async function fetchResults() {
    setActiveFilter('results');
    await loadMatches(state.root.dataset.resultsUrl, 'Recent results are not available yet.');
}

export function renderMatches(matches, emptyMessage = 'No matches available.') {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    if (! Array.isArray(matches) || matches.length === 0) {
        renderEmpty(emptyMessage);
        return;
    }

    target.innerHTML = matches.map((match) => renderMatchCard(match)).join('');
    target.querySelectorAll('[data-load-tv]').forEach((button) => {
        button.addEventListener('click', () => loadTvChannelsForCard(button.closest('[data-match-card]')));
    });
}

export function renderMatchCard(match) {
    const statusType = safeText(match?.status_type || 'unknown');
    const home = match?.home_team || {};
    const away = match?.away_team || {};
    const score = match?.score || {};
    const displayScore = score.home !== null && score.home !== undefined && score.away !== null && score.away !== undefined
        ? `${safeText(score.home)} - ${safeText(score.away)}`
        : safeText(formatTime(match?.time) || 'TBD');
    const homeBadge = safeText(home.badge || '/brand/rifi-logo.png');
    const awayBadge = safeText(away.badge || '/brand/rifi-logo.png');
    const eventId = safeText(match?.id || '');
    const detailsUrl = safeText(match?.event_url || `/sports/football/event/${eventId}`);

    return `
        <article class="football-match-card" data-match-card data-event-id="${eventId}">
            <header class="football-match-card__header">
                <span>${safeText(match?.league?.name || 'Football')}</span>
                <b class="football-status-badge football-status-badge--${statusType}">${safeText(match?.status || 'Unknown')}</b>
            </header>
            <div class="football-scoreline">
                <div class="football-team">
                    <img src="${homeBadge}" alt="" loading="lazy" onerror="this.src='/brand/rifi-logo.png'">
                    <strong>${safeText(home.name || 'Home')}</strong>
                </div>
                <a href="${detailsUrl}" class="football-scoreline__score" aria-label="Open match details">${displayScore}</a>
                <div class="football-team football-team--away">
                    <img src="${awayBadge}" alt="" loading="lazy" onerror="this.src='/brand/rifi-logo.png'">
                    <strong>${safeText(away.name || 'Away')}</strong>
                </div>
            </div>
            <footer class="football-match-card__meta">
                <span>${safeText(formatDate(match?.date))}${match?.time ? ` · ${safeText(formatTime(match.time))}` : ''}</span>
                ${match?.venue ? `<span>${safeText(match.venue)}</span>` : ''}
            </footer>
            <section class="football-tv-box" data-tv-box>
                <button type="button" class="football-tv-toggle" data-load-tv>Show TV channels</button>
            </section>
        </article>
    `;
}

export function renderTvChannels(channels) {
    if (! Array.isArray(channels) || channels.length === 0) {
        return '<p class="football-empty">Broadcast information is not available for this match.</p>';
    }

    const filtered = state.activeCountry === 'All'
        ? channels
        : channels.filter((channel) => (channel.country || '').toLowerCase() === state.activeCountry.toLowerCase());
    const visible = filtered.length > 0 ? filtered : channels;
    const hasAvailable = visible.some((channel) => channel.available);
    const helper = hasAvailable ? '' : '<p class="football-tv-note">Channels found, but not available in our playlist yet.</p>';

    return `
        ${helper}
        <div class="football-tv-list">
            ${visible.map((channel) => channel.available ? buildWatchButton(channel) : buildUnavailableChannel(channel)).join('')}
        </div>
    `;
}

export function renderLoading() {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    target.innerHTML = Array.from({ length: 5 }).map(() => `
        <article class="football-match-card football-match-card--skeleton">
            <span></span><div></div><strong></strong><p></p>
        </article>
    `).join('');
}

export function renderError(message) {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    target.innerHTML = `<div class="football-state football-state--error"><strong>Could not load matches</strong><p>${safeText(message || 'Please try again shortly.')}</p></div>`;
}

export function renderEmpty(message) {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    target.innerHTML = `<div class="football-state"><strong>No matches</strong><p>${safeText(message)}</p></div>`;
}

export function setActiveFilter(filter) {
    state.activeFilter = filter;
    state.root?.querySelectorAll('[data-football-filter]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.footballFilter === filter);
    });
}

export function safeText(value) {
    const text = value === null || value === undefined ? '' : String(value);
    return text.replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[char]);
}

export function buildWatchButton(channel) {
    const fallback = channel.watch_url || (channel.matched_channel_slug ? `/watch/${channel.matched_channel_slug}` : null);
    if (! fallback) return buildUnavailableChannel(channel);

    return `
        <a href="${safeText(fallback)}" class="football-watch-btn">
            <span>${channel.logo ? `<img src="${safeText(channel.logo)}" alt="" loading="lazy">` : safeText((channel.matched_channel_name || channel.channel || 'C').slice(0, 1))}</span>
            <strong>${safeText(channel.matched_channel_name || channel.channel || 'Channel')}</strong>
            <em>Watch</em>
        </a>
    `;
}

export function formatDate(date) {
    if (! date) return '';
    try {
        return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${date}T00:00:00`));
    } catch {
        return date;
    }
}

export function formatTime(time) {
    if (! time) return '';
    return String(time).slice(0, 5);
}

export function autoRefreshLiveMatches() {
    clearInterval(state.refreshTimer);
    state.refreshTimer = setInterval(() => {
        if (document.visibilityState !== 'visible') return;
        if (['today', 'date'].includes(state.activeFilter)) {
            fetchTodayMatches();
        }
    }, 60000);
}

function bindFootballPage() {
    state.root.querySelectorAll('[data-football-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.footballFilter;
            if (filter === 'today' || filter === 'live') return fetchTodayMatches();
            if (filter === 'tomorrow') return fetchMatchesByDate(offsetDate(1));
            if (filter === 'yesterday') return fetchMatchesByDate(offsetDate(-1));
            if (filter === 'upcoming') return fetchUpcomingMatches();
            if (filter === 'results') return fetchResults();
        });
    });

    state.root.querySelector('[data-football-date]')?.addEventListener('change', (event) => {
        if (event.target.value) fetchMatchesByDate(event.target.value);
    });

    state.root.querySelector('[data-football-refresh]')?.addEventListener('click', () => {
        if (state.activeFilter === 'upcoming') return fetchUpcomingMatches();
        if (state.activeFilter === 'results') return fetchResults();
        return fetchTodayMatches();
    });

    state.root.querySelectorAll('[data-tv-country]').forEach((button) => {
        button.addEventListener('click', () => {
            state.activeCountry = button.dataset.tvCountry || 'All';
            state.root.querySelectorAll('[data-tv-country]').forEach((item) => item.classList.toggle('is-active', item === button));
            rerenderLoadedTvChannels();
        });
    });
}

async function loadMatches(url, emptyMessage) {
    renderLoading();
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (! response.ok) throw new Error(response.status === 429 ? 'Rate limit reached. Please retry shortly.' : 'The match service returned an error.');
        const payload = await response.json();
        renderMatches(payload.data || [], emptyMessage);
    } catch (error) {
        renderError(error.message);
    }
}

async function loadTvChannelsForCard(card) {
    if (! card) return;
    const eventId = card.dataset.eventId;
    const box = card.querySelector('[data-tv-box]');
    if (! eventId || ! box) return;

    box.innerHTML = '<p class="football-empty">Loading TV channels...</p>';

    try {
        const channels = await fetchTvChannels(eventId);
        box.dataset.tvLoaded = 'true';
        box.innerHTML = renderTvChannels(channels);
    } catch (error) {
        box.innerHTML = `<p class="football-empty">${safeText(error.message)}</p>`;
    }
}

async function fetchTvChannels(eventId) {
    if (state.tvCache.has(eventId)) return state.tvCache.get(eventId);

    const template = state.root?.dataset.tvUrlTemplate || '/api/football/event/__EVENT_ID__/tv';
    const response = await fetch(template.replace('__EVENT_ID__', encodeURIComponent(eventId)), { headers: { Accept: 'application/json' } });
    if (! response.ok) throw new Error(response.status === 429 ? 'TV request rate limited. Try again shortly.' : 'TV channels could not be loaded.');
    const payload = await response.json();
    const channels = payload.data || [];
    state.tvCache.set(eventId, channels);
    return channels;
}

function buildUnavailableChannel(channel) {
    return `
        <div class="football-channel-disabled" aria-disabled="true">
            <strong>${safeText(channel.channel || channel.name || 'Channel')}</strong>
            ${channel.country ? `<small>${safeText(channel.country)}</small>` : ''}
            <em>Not in playlist</em>
        </div>
    `;
}

function rerenderLoadedTvChannels() {
    state.root?.querySelectorAll('[data-match-card]').forEach((card) => {
        const eventId = card.dataset.eventId;
        const box = card.querySelector('[data-tv-box]');
        if (box?.dataset.tvLoaded === 'true' && state.tvCache.has(eventId)) {
            box.innerHTML = renderTvChannels(state.tvCache.get(eventId));
        }
    });
}

function offsetDate(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

function initFootballEventPage() {
    const root = document.querySelector(SELECTORS.eventRoot);
    if (! root) return;

    const eventId = root.dataset.eventId;
    const target = root.querySelector('[data-event-tv-channels]');
    if (! eventId || ! target) return;

    fetch(`/api/football/event/${encodeURIComponent(eventId)}/tv`, { headers: { Accept: 'application/json' } })
        .then((response) => response.ok ? response.json() : Promise.reject(new Error('Could not load TV channels.')))
        .then((payload) => {
            target.innerHTML = renderTvChannels(payload.data || []);
        })
        .catch((error) => {
            target.innerHTML = `<p class="football-empty">${safeText(error.message)}</p>`;
        });
}

document.addEventListener('DOMContentLoaded', initFootballLivescore);

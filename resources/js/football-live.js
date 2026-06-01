const SELECTORS = {
    root: '[data-football-live]',
    eventRoot: '[data-football-event-page]',
    matches: '[data-football-matches]',
    status: '[data-football-status]',
    count: '[data-football-count]',
};

const state = {
    root: null,
    activeFilter: 'today',
    activeCountry: 'All',
    activeLeague: 'All',
    searchQuery: '',
    currentMatches: [],
    refreshTimer: null,
    tvCache: new Map(),
};

const ICONS = {
    calendar: '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18"></path></svg>',
    clock: '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>',
    football: '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="m9 9 3-2 3 2-1 4h-4L9 9Z"></path></svg>',
    signal: '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20h.01"></path><path d="M7 20a5 5 0 0 0-5-5"></path><path d="M12 20A10 10 0 0 0 2 10"></path><path d="M17 20A15 15 0 0 0 2 5"></path></svg>',
    trophy: '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"></path><path d="M12 17v4"></path><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"></path><path d="M5 5H3v2a4 4 0 0 0 4 4"></path><path d="M19 5h2v2a4 4 0 0 1-4 4"></path></svg>',
    tv: '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="12" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path></svg>',
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
    await loadMatches(state.root.dataset.todayUrl);
}

export async function fetchLiveMatches() {
    setActiveFilter('live');
    await loadMatches(state.root.dataset.todayUrl);
}

export async function fetchMatchesByDate(date) {
    setActiveFilter('date');
    const url = new URL(state.root.dataset.dateUrl, window.location.origin);
    url.searchParams.set('date', date);
    await loadMatches(url.toString());
}

export async function fetchUpcomingMatches() {
    setActiveFilter('upcoming');
    await loadMatches(state.root.dataset.upcomingUrl);
}

export async function fetchResults() {
    setActiveFilter('results');
    await loadMatches(state.root.dataset.resultsUrl);
}

export function renderMatches(matches, emptyMessage = 'No matches available.') {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    state.currentMatches = Array.isArray(matches) ? matches : [];
    const visibleMatches = filterMatches(state.currentMatches);

    updateMatchCount(visibleMatches.length);

    if (visibleMatches.length === 0) {
        renderEmpty(emptyMessage);
        return;
    }

    target.innerHTML = groupMatchesByLeague(visibleMatches).map(renderLeagueGroup).join('');
    target.querySelectorAll('[data-load-tv]').forEach((button) => {
        button.addEventListener('click', () => loadTvChannelsForCard(button.closest('[data-match-card]')));
    });
}

export function renderLeagueGroup(group) {
    return `
        <section class="football-league-group" aria-label="${safeText(group.league)} matches">
            <header class="football-league-group__header">
                <span>${ICONS.trophy}</span>
                <div>
                    <h3>${safeText(group.league)}</h3>
                    <p>${group.matches.length} ${group.matches.length === 1 ? 'match' : 'matches'}</p>
                </div>
            </header>
            <div class="football-league-group__matches">
                ${group.matches.map((match) => renderMatchCard(match)).join('')}
            </div>
        </section>
    `;
}

export function renderMatchCard(match) {
    const statusType = safeText(match?.status_type || 'unknown');
    const isLive = isLiveMatch(match);
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
    const homeName = safeText(home.name || 'Team pending');
    const awayName = safeText(away.name || 'Team pending');
    const leagueName = safeText(match?.league?.name || 'Football');
    const formattedDate = safeText(formatDate(match?.date));
    const formattedTime = safeText(formatTime(match?.time));
    const statusLabel = safeText(match?.status || (formattedTime ? 'Scheduled' : 'Match status'));

    return `
        <article class="football-match-card ${isLive ? 'is-live' : ''}" data-match-card data-event-id="${eventId}" data-league="${leagueName}">
            <header class="football-match-card__header">
                <span>${ICONS.trophy}${leagueName}</span>
                <b class="football-status-badge football-status-badge--${statusType}">${isLive ? `${ICONS.signal} Live` : statusLabel}</b>
            </header>
            <div class="football-scoreline">
                <div class="football-team">
                    <img src="${homeBadge}" alt="" loading="lazy" onerror="this.src='/brand/rifi-logo.png'">
                    <strong>${homeName}</strong>
                </div>
                <a href="${detailsUrl}" class="football-scoreline__score" aria-label="Open match details">${displayScore}</a>
                <div class="football-team football-team--away">
                    <img src="${awayBadge}" alt="" loading="lazy" onerror="this.src='/brand/rifi-logo.png'">
                    <strong>${awayName}</strong>
                </div>
            </div>
            <footer class="football-match-card__meta">
                <span>${ICONS.calendar}${formattedDate || 'Date unavailable'}</span>
                ${formattedTime ? `<span>${ICONS.clock}${formattedTime}</span>` : ''}
                ${match?.venue ? `<span>${safeText(match.venue)}</span>` : ''}
            </footer>
            <section class="football-tv-box" data-tv-box>
                <button type="button" class="football-tv-toggle" data-load-tv>${ICONS.tv} Check TV channels</button>
            </section>
        </article>
    `;
}

export function renderTvChannels(channels) {
    if (! Array.isArray(channels) || channels.length === 0) {
        return '<p class="football-empty">No TV channels are listed for this match yet.</p>';
    }

    const filtered = state.activeCountry === 'All'
        ? channels
        : channels.filter((channel) => (channel.country || '').toLowerCase() === state.activeCountry.toLowerCase());
    const visible = filtered.length > 0 ? filtered : channels;
    const hasAvailable = visible.some((channel) => channel.available);
    const helper = hasAvailable ? '<p class="football-tv-note football-tv-note--available">Watch links available</p>' : '<p class="football-tv-note">TV listings are available, but no matching RifiMedia channel is ready yet.</p>';

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

    setShellBusy(true);
    updateMatchCount('Loading matches...');

    target.innerHTML = `
        <section class="football-league-group football-league-group--skeleton">
            <header class="football-league-group__header">
                <span></span><div><h3></h3><p></p></div>
            </header>
            <div class="football-league-group__matches">
                ${Array.from({ length: 6 }).map(() => `
                    <article class="football-match-card football-match-card--skeleton">
                        <span></span><div></div><strong></strong><p></p>
                    </article>
                `).join('')}
            </div>
        </section>
    `;
}

export function renderError(message) {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    setShellBusy(false);
    updateMatchCount('Match feed unavailable');

    target.innerHTML = `<div class="football-state football-state--error"><span>${ICONS.signal}</span><strong>Could not load matches</strong><p>${safeText(message || 'Please try again shortly.')}</p><button type="button" class="football-tv-toggle" data-football-retry>Retry</button></div>`;
    target.querySelector('[data-football-retry]')?.addEventListener('click', () => {
        if (state.activeFilter === 'upcoming') return fetchUpcomingMatches();
        if (state.activeFilter === 'results') return fetchResults();
        return fetchTodayMatches();
    });
}

export function renderEmpty(message) {
    const target = state.root?.querySelector(SELECTORS.matches);
    if (! target) return;

    setShellBusy(false);
    updateMatchCount('No matches');

    target.innerHTML = `<div class="football-state"><span>${ICONS.football}</span><strong>No matches available for this filter right now.</strong><p>${safeText(message || 'Try changing the date, league, region, or search keyword.')}</p></div>`;
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
            <em>${ICONS.tv} Watch</em>
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
        if (state.activeFilter === 'live') {
            fetchLiveMatches();
        } else if (['today', 'date'].includes(state.activeFilter)) {
            fetchTodayMatches();
        }
    }, 60000);
}

function bindFootballPage() {
    state.root.querySelectorAll('[data-football-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.footballFilter;
            if (filter === 'today') return fetchTodayMatches();
            if (filter === 'live') return fetchLiveMatches();
            if (filter === 'tomorrow') return fetchMatchesByDate(offsetDate(1));
            if (filter === 'yesterday') return fetchMatchesByDate(offsetDate(-1));
            if (filter === 'upcoming') return fetchUpcomingMatches();
            if (filter === 'results') return fetchResults();
        });
    });

    state.root.querySelector('[data-football-date]')?.addEventListener('change', (event) => {
        if (event.target.value) fetchMatchesByDate(event.target.value);
    });

    let searchTimer = null;
    state.root.querySelector('[data-football-search]')?.addEventListener('input', (event) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.searchQuery = String(event.target.value || '').trim().toLowerCase();
            renderMatches(state.currentMatches);
        }, 180);
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

    state.root.querySelectorAll('[data-football-league]').forEach((button) => {
        button.addEventListener('click', () => {
            state.activeLeague = button.dataset.footballLeague || 'All';
            state.root.querySelectorAll('[data-football-league]').forEach((item) => item.classList.toggle('is-active', item === button));
            renderMatches(state.currentMatches, 'Try changing the date, league, region, or search keyword.');
        });
    });
}

async function loadMatches(url, emptyMessage) {
    renderLoading();
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (! response.ok) throw new Error(response.status === 429 ? 'Too many match requests. Please retry shortly.' : 'The match feed is temporarily unavailable.');
        const payload = await response.json();
        renderMatches(payload.data || [], emptyMessage);
        setShellBusy(false);
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

function filterMatches(matches) {
    return matches.filter((match) => {
        const leagueName = String(match?.league?.name || '');
        const statusType = String(match?.status_type || '').toLowerCase();
        const haystack = [
            match?.league?.name,
            match?.home_team?.name,
            match?.away_team?.name,
            match?.status,
            match?.venue,
        ].filter(Boolean).join(' ').toLowerCase();
        const leagueMatches = state.activeLeague === 'All' || leagueName === state.activeLeague;
        const liveMatches = state.activeFilter !== 'live' || statusType.includes('live') || ['1h', '2h', 'ht'].includes(statusType);
        const searchMatches = state.searchQuery === '' || haystack.includes(state.searchQuery);

        return leagueMatches && liveMatches && searchMatches;
    });
}

function groupMatchesByLeague(matches) {
    const groups = new Map();

    matches.forEach((match) => {
        const league = match?.league?.name || 'Football';
        if (!groups.has(league)) {
            groups.set(league, []);
        }
        groups.get(league).push(match);
    });

    return Array.from(groups.entries()).map(([league, groupedMatches]) => ({
        league,
        matches: groupedMatches,
    }));
}

function isLiveMatch(match) {
    const statusType = String(match?.status_type || '').toLowerCase();
    const status = String(match?.status || '').toLowerCase();

    return statusType.includes('live') || status.includes('live') || ['1h', '2h', 'ht'].includes(statusType);
}

function updateMatchCount(value) {
    const target = state.root?.querySelector(SELECTORS.count);
    if (!target) return;

    if (typeof value === 'number') {
        target.textContent = `${value} ${value === 1 ? 'match' : 'matches'}`;
        return;
    }

    target.textContent = value;
}

function setShellBusy(isBusy) {
    state.root?.querySelector('.football-match-shell')?.setAttribute('aria-busy', isBusy ? 'true' : 'false');
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

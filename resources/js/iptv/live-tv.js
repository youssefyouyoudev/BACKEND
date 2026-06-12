import { initResilientPlayer } from '../live-player-resilience';
import { t } from '../i18n';

const STORAGE = {
    catalog: 'rifi-live-tv-catalog-v4',
    favorites: 'rifi-live-tv-favorites-v2',
    recent: 'rifi-live-tv-recent-v2',
    last: 'rifi-live-tv-last-v2',
};

const QUALITY_ORDER = ['HD', 'FHD', '4K', 'SD'];
const CHANNEL_ORDER = [
    'beIN Sports 1', 'beIN Sports 2', 'beIN Sports 3', 'beIN Sports 4', 'beIN Sports 5',
    'beIN Sports 6', 'beIN Sports 7', 'beIN Sports 8', 'beIN Sports 9', 'beIN Sports News',
    'beIN Sports Max 1 - World Cup', 'beIN Sports Max 2 - World Cup',
    'beIN Sports Max 3 - World Cup', 'beIN Sports Max 4 - World Cup',
    'beIN Sports Max 5 - World Cup', 'beIN Sports Max 6 - World Cup',
    'Arryadia',
    'AD Sport 1', 'AD Sport 2', 'AD Sport Premium 1', 'AD Sport Premium 2',
    'SSC 1', 'SSC 2', 'SSC 3', 'SSC 4', 'SSC 5', 'SSC Extra 1', 'SSC Extra 2', 'SSC Extra 3',
    'Al Kass One', 'Al Kass Two', 'Al Kass Four',
    'Dubai Sports 1', 'Dubai Sports 2', 'OnTime Sports', 'OnTime Sports 2',
];

const readStorage = (key, fallback) => {
    try {
        const value = JSON.parse(localStorage.getItem(key));
        return value ?? fallback;
    } catch {
        return fallback;
    }
};

const writeStorage = (key, value) => {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.warn('[RifiLiveTV] Could not save local state.', error);
    }
};

const fold = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const isCuratedSportsChannel = (channel) =>
    /(bein\s*sports|beinsports|arryadia|arriyadia|الرياضية المغربية|abu dhabi sport|ad sports?|أبوظبي الرياضية|ssc\s*[1-5]|ssc sports?|ssc extra|alkass|al kass|الكأس الرياضية|dubai sports?|دبي الرياضية|ontime sports?|on time sports?|أون تايم سبورتس|ksa sports?|saudi sports?|السعودية الرياضية|kuwait sports?|الكويت الرياضية|oman sports?|عمان الرياضية|bahrain sports?|البحرين الرياضية|iraqia sports?|iraqi sports?|العراقية الرياضية|jordan sports?|الأردن الرياضية|world cup|fifa world cup|كأس العالم)/i
        .test(`${channel?.name || ''} ${channel?.original_name || ''} ${channel?.group_title || ''}`);

const channelSection = (channel) => {
    const value = `${channel?.name || ''} ${channel?.original_name || ''} ${channel?.group_title || ''}`;
    if (/\bbein\s*sports?\b|beinsports/i.test(value)) return 'beIN Sports';
    if (/arryadia|arriyadia|الرياضية المغربية/i.test(value)) return 'Morocco';
    if (/world cup|fifa world cup|كأس العالم/i.test(value)) return 'World Cup';
    return 'Gulf Sports';
};

export function detectQuality(name, url = '', metadata = {}) {
    const value = `${name || ''} ${url || ''} ${metadata.quality || ''} ${metadata.extension || ''}`.toUpperCase();
    if (/\b(4K|UHD|2160P)\b/.test(value)) return '4K';
    if (/\b(FHD|FULL[\s._-]*HD|1080P)\b/.test(value)) return 'FHD';
    if (/\b(HD\+|HD|720P|HEVC|H\.?265)\b/.test(value)) return 'HD';
    return 'SD';
}

export function normalizeChannelName(name) {
    return String(name || 'Live channel')
        .replace(/^\s*(?:\[(?:ES|FR|AR|MA|MAR|UK|US|EN|PT|DE|IT)]|\((?:ES|FR|AR|MA|MAR|UK|US|EN|PT|DE|IT)\)|\|(?:ES|FR|AR|MA|MAR|UK|US|EN|PT|DE|IT)\|)\s*/i, '')
        .replace(/\b(?:UHD|4K|2160P|FHD|FULL[\s._-]*HD|1080P|HD\+|HD|720P|SD|HEVC|H\.?265)\b/gi, ' ')
        .replace(/(?:^|\s)\+\s*$/g, ' ')
        .replace(/\s{2,}/g, ' ')
        .replace(/[\s._-]+$/g, '')
        .trim() || 'Live channel';
}

export function detectCountryOrLanguage(name, group = '') {
    const value = fold(`${name} ${group}`);
    if (/(^\s*(?:\[ma\]|\[mar\]|\(ma\)|\(mar\)|\|ma\||\|mar\|)|morocco|maroc|maghreb)/i.test(`${name} ${group}`)) return { country: 'Morocco', language: 'Arabic' };
    if (/(^\s*(?:\[fr\]|\(fr\)|\|fr\|)|france|francais|french)/i.test(`${name} ${group}`)) return { country: 'France', language: 'French' };
    if (/(^\s*(?:\[es\]|\(es\)|\|es\|)|spain|espana|espanol|spanish)/i.test(`${name} ${group}`)) return { country: 'Spain', language: 'Spanish' };
    if (/(^\s*(?:\[ar\]|\(ar\)|\|ar\|)|arab|arabic|arabe|العرب)/i.test(value)) return { country: '', language: 'Arabic' };
    return { country: '', language: 'Global' };
}

export function detectCleanCategory(group, name = '') {
    const value = fold(`${group} ${name}`);
    if (/(sport|bein|laliga|football|soccer|nba|formula|ufc|wwe)/.test(value)) return 'Sports';
    if (/(movie|cinema|film|vod)/.test(value)) return 'Movies';
    if (/(kid|child|cartoon|nick|disney|junior)/.test(value)) return 'Kids';
    if (/(news|info|actualit|cnn|bbc|al jazeera)/.test(value)) return 'News';
    const locale = detectCountryOrLanguage(name, group);
    if (locale.country) return locale.country;
    if (locale.language === 'Arabic') return 'Arabic';
    return 'General';
}

export function sortQualityVariants(variants) {
    return [...variants].sort((a, b) => QUALITY_ORDER.indexOf(a.quality) - QUALITY_ORDER.indexOf(b.quality));
}

export function getBestDefaultVariant(channelGroup) {
    return sortQualityVariants(channelGroup?.variants || []).find((variant) => variant.id && variant.playable !== false) || null;
}

export function groupChannelVariants(channels) {
    const groups = new Map();

    (channels || []).forEach((channel) => {
        if (!channel?.id) return;
        const cleanName = normalizeChannelName(channel.name || channel.original_name);
        const locale = detectCountryOrLanguage(channel.name, channel.group_title);
        const key = fold(cleanName);
        const id = key.replace(/[^a-z0-9]+/g, '-');
        const quality = detectQuality(channel.name, '', {
            quality: channel.quality_label,
            extension: channel.extension,
        });
        const variant = {
            ...channel,
            quality,
            rawName: channel.original_name || channel.name || cleanName,
            playable: channel.playback_status?.playable !== false,
        };

        if (!groups.has(key)) {
            groups.set(key, {
                id,
                name: cleanName,
                logo: channel.logo || channel.thumbnail || '',
                category: detectCleanCategory(channel.group_title, channel.name),
                country: locale.country,
                language: locale.language,
                rawNames: [],
                variants: [],
            });
        }

        const group = groups.get(key);
        group.rawNames.push(variant.rawName);
        if (!group.logo && channel.logo) group.logo = channel.logo;
        if (!group.variants.some((existing) => existing.id === variant.id)) group.variants.push(variant);
    });

    return [...groups.values()]
        .map((group) => {
            const variants = sortQualityVariants(group.variants);
            const qualityOptions = variants.filter((variant, index) =>
                variants.findIndex((candidate) => candidate.quality === variant.quality) === index);

            return { ...group, variants, qualityOptions };
        })
        .sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));
}

const destroyPlayer = (state) => {
    if (state.playerController) {
        state.playerController.destroy();
        state.playerController = null;
    }
    if (state.hls) {
        state.hls.destroy();
        state.hls = null;
    }
    if (state.mpegts) {
        try {
            state.mpegts.unload();
            state.mpegts.detachMediaElement();
            state.mpegts.destroy();
        } catch (error) {
            console.debug('[RifiLiveTV] MPEG-TS cleanup skipped.', error);
        }
        state.mpegts = null;
    }
    clearTimeout(state.reconnectTimer);
    clearInterval(state.stallTimer);
    state.reconnectTimer = null;
    state.stallTimer = null;
};

window.liveTvPage = ({ initialChannels = [], initialChannelId = null, initialCategory = '', fallbackLogo = '' }) => ({
    rawChannels: initialChannels,
    channelGroups: [],
    activeGroup: null,
    activeVariant: null,
    attemptedVariantIds: [],
    search: '',
    activeCategory: 'All Channels',
    viewMode: 'grid',
    loadingCatalog: false,
    loadingPlayer: false,
    playerError: false,
    playerErrorMessage: '',
    fallbackActive: false,
    reconnecting: false,
    reconnectMessage: '',
    externalPlayerUrl: '',
    fallbackLogo,
    favorites: readStorage(STORAGE.favorites, []),
    recent: readStorage(STORAGE.recent, []),
    categories: ['All Channels'],
    toastMessage: '',
    toastTimer: null,
    hls: null,
    mpegts: null,
    requestId: 0,
    recoveryAttempts: 0,
    reconnectTimer: null,
    stallTimer: null,
    lastPlaybackTime: 0,
    stalledChecks: 0,
    focusedIndex: 0,
    playerController: null,
    keydownHandler: null,
    signedUrlRefreshes: 0,

    get filteredGroups() {
        const query = fold(this.search.trim());
        const groups = this.channelGroups.filter((group) => {
            const matchesCategory = this.activeCategory === 'All Channels' || group.category === this.activeCategory;
            const matchesSearch = !query || fold(`${group.name} ${group.rawNames.join(' ')} ${group.category} ${group.country} ${group.language}`).includes(query);
            return matchesCategory && matchesSearch;
        });

        return this.activeCategory === 'Recently Watched'
            ? groups.sort((a, b) => this.recent.indexOf(a.id) - this.recent.indexOf(b.id))
            : groups;
    },

    get availableQualities() {
        return this.activeGroup?.qualityOptions.map((variant) => variant.quality) || [];
    },

    get isFavorite() {
        return Boolean(this.activeGroup && this.favorites.includes(this.activeGroup.id));
    },

    get showPlayerFallback() {
        return this.fallbackActive || (!this.loadingCatalog && this.channelGroups.length === 0);
    },

    init() {
        const cached = readStorage(STORAGE.catalog, null);
        if (cached?.channels?.length) {
            this.rawChannels = cached.channels;
            this.toast(t('Using saved channel list. Refreshing in background.'));
        }
        this.rebuildGroups();
        if (initialCategory && this.categories.includes(initialCategory)) {
            this.activeCategory = initialCategory;
        }

        const requestedGroup = initialChannelId
            ? this.channelGroups.find((group) => group.variants.some((variant) => Number(variant.id) === Number(initialChannelId)))
            : null;
        const last = readStorage(STORAGE.last, null);
        const lastGroup = last ? this.channelGroups.find((group) => group.id === last.groupId) : null;
        const first = requestedGroup || lastGroup || this.channelGroups.find((group) => getBestDefaultVariant(group));
        if (first) {
            const preferred = requestedGroup
                ? first.variants.find((variant) => Number(variant.id) === Number(initialChannelId))
                : first.variants.find((variant) => Number(variant.id) === Number(last?.variantId));
            this.watchGroup(first, preferred || getBestDefaultVariant(first), false);
        } else if (this.channelGroups.length) {
            this.showPlayerError(t('Streams are listed, but their provider is not enabled in the approved domain allowlist.'));
        }

        this.refreshCatalog();
        this.keydownHandler = (event) => this.handleKeydown(event);
        window.addEventListener('keydown', this.keydownHandler);
    },

    destroy() {
        destroyPlayer(this);
        clearTimeout(this.toastTimer);
        if (this.keydownHandler) {
            window.removeEventListener('keydown', this.keydownHandler);
            this.keydownHandler = null;
        }
    },

    rebuildGroups() {
        this.channelGroups = groupChannelVariants(this.rawChannels);
        this.categories = [
            'All Channels',
            ...new Set(this.channelGroups.map((group) => group.category).filter(Boolean)),
        ];
        if (!this.categories.includes(this.activeCategory)) {
            this.activeCategory = 'All Channels';
        }
        if (this.activeGroup) {
            this.activeGroup = this.channelGroups.find((group) => group.id === this.activeGroup.id) || this.activeGroup;
        }
    },

    async refreshCatalog() {
        this.loadingCatalog = true;
        const collected = [];
        try {
            const response = await fetch('/api/tv/channels', {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error(`Catalog request failed with HTTP ${response.status}`);
            const payload = await response.json();
            if (!payload.success || !Array.isArray(payload.channels)) {
                throw new Error(t('Catalog response did not contain a channels array.'));
            }
            collected.push(...payload.channels);

            this.rawChannels = collected;
            writeStorage(STORAGE.catalog, { timestamp: Date.now(), channels: this.rawChannels });
            this.rebuildGroups();

            const activeStillPublic = this.activeGroup
                && this.channelGroups.some((group) => group.id === this.activeGroup.id);

            if (!activeStillPublic) {
                this.activeGroup = null;
                this.activeVariant = null;
                destroyPlayer(this);
                const first = this.channelGroups.find((group) => getBestDefaultVariant(group));
                if (first) {
                    this.fallbackActive = false;
                    this.watchGroup(first);
                } else {
                    this.fallbackActive = true;
                    this.playerError = false;
                }
            }
        } catch (error) {
            console.error('[RifiLiveTV] Catalog refresh failed.', error);
            if (this.channelGroups.length) {
                this.toast(t('Using saved channel list. Refreshing in background.'));
            }
        } finally {
            this.loadingCatalog = false;
        }
    },

    async watchGroup(group, preferredVariant = null, scroll = true) {
        this.activeGroup = group;
        this.fallbackActive = false;
        this.attemptedVariantIds = [];
        this.signedUrlRefreshes = 0;
        const variant = preferredVariant || getBestDefaultVariant(group);
        if (!variant) {
            this.fallbackActive = true;
            this.showPlayerError(t('This channel has no playable version.'), true);
            return;
        }
        this.rememberRecent(group.id);
        await this.playVariant(variant, false);
        if (scroll && window.matchMedia('(max-width: 767px)').matches) {
            this.$refs.playerSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    },

    async getFreshPlayUrl(channelId) {
        const response = await fetch(`/api/tv/channels/${channelId}/play-url`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Could not get play URL: ${response.status}`);
        }

        const payload = await response.json();
        if (!payload.success || !payload.url) {
            throw new Error('Missing protected play URL');
        }

        return payload.url;
    },

    async playVariant(variant, announce = true, isSignedUrlRefresh = false) {
        const requestId = ++this.requestId;
        this.activeVariant = variant;
        this.attemptedVariantIds.push(variant.id);
        if (!isSignedUrlRefresh) this.signedUrlRefreshes = 0;
        this.loadingPlayer = true;
        this.reconnecting = isSignedUrlRefresh;
        this.reconnectMessage = isSignedUrlRefresh ? t('Stream link expired, refreshing...') : '';
        this.playerError = false;
        this.playerErrorMessage = '';
        this.fallbackActive = false;
        this.recoveryAttempts = 0;
        destroyPlayer(this);

        try {
            const playUrl = await this.getFreshPlayUrl(variant.id);
            if (requestId !== this.requestId) return;
            const freshVariant = {
                ...variant,
                public_play_url: playUrl,
            };
            this.activeVariant = freshVariant;
            const source = playUrl ? {
                url: playUrl,
                browser_url: playUrl,
                external_url: playUrl,
                type: variant.stream_type || variant.extension || 'stream',
                requires_external_player: false,
            } : null;
            if (!source?.url) {
                this.tryNextVariant(
                    freshVariant.playback_status?.message || t('This channel has no approved playable source.'),
                    true
                );
                return;
            }
            this.externalPlayerUrl = source.external_url || source.url;
            this.startPlayer(source, requestId);
            writeStorage(STORAGE.last, { groupId: this.activeGroup.id, variantId: variant.id });
            history.replaceState(null, '', `${window.location.pathname}?channel=${variant.id}`);
            if (announce) this.toast(t('Switched to :quality', { quality: variant.quality }));
        } catch (error) {
            if (requestId !== this.requestId) return;
            console.warn('[RifiLiveTV] Could not load channel metadata.', {
                channelId: variant.id,
                message: error instanceof Error ? error.message : String(error),
            });
            if (isSignedUrlRefresh) {
                this.showPlayerError(t('Unable to play this channel right now. Please try again later.'));
                return;
            }
            this.tryNextVariant(t('The channel service could not load this source.'));
        }
    },

    startPlayer(source, requestId) {
        const video = this.$refs.video;
        if (!video) return;
        const playableUrl = source.browser_url || source.url;
        if (source.requires_external_player && window.location.protocol === 'https:' && source.browser_url === source.url) {
            this.tryNextVariant();
            return;
        }

        destroyPlayer(this);
        this.playerController = initResilientPlayer(video, playableUrl, {
            streamType: source.type,
            Hls: window.Hls,
            mpegts: window.mpegts,
            onLoading: () => {
                if (requestId !== this.requestId) return;
                this.loadingPlayer = true;
            },
            onReconnecting: (message) => {
                if (requestId !== this.requestId) return;
                this.loadingPlayer = false;
                this.reconnecting = true;
                this.reconnectMessage = message;
            },
            onPlaying: () => {
                if (requestId !== this.requestId) return;
                this.loadingPlayer = false;
                this.reconnecting = false;
                this.reconnectMessage = '';
                this.playerError = false;
                this.fallbackActive = false;
            },
            onCanPlay: () => {
                if (requestId !== this.requestId) return;
                this.loadingPlayer = false;
            },
            onFatal: (message) => {
                if (requestId !== this.requestId) return;
                this.reconnecting = false;
                this.reconnectMessage = '';
                this.tryNextVariant(message);
            },
            onForbidden: () => {
                if (requestId !== this.requestId) return;
                this.refreshSignedUrlAfterForbidden(this.activeVariant, requestId);
            },
        });
    },

    async refreshSignedUrlAfterForbidden(variant, requestId) {
        destroyPlayer(this);
        if (requestId !== this.requestId) return;

        if (this.signedUrlRefreshes >= 1) {
            this.showPlayerError(t('Unable to play this channel right now. Please try again later.'));
            return;
        }

        this.signedUrlRefreshes += 1;
        this.loadingPlayer = false;
        this.reconnecting = true;
        this.reconnectMessage = t('Stream link expired, refreshing...');
        await this.playVariant(variant, false, true);
    },

    tryNextVariant(finalMessage = t('This version is not working. Try another quality.'), useFallback = false) {
        const variants = sortQualityVariants(this.activeGroup?.variants || []);
        const next = variants.find((variant) => !this.attemptedVariantIds.includes(variant.id));
        if (next) {
            this.playVariant(next, false);
            return;
        }
        this.showPlayerError(finalMessage, useFallback);
    },

    showPlayerError(message, useFallback = false) {
        this.loadingPlayer = false;
        this.reconnecting = false;
        this.fallbackActive = useFallback;
        this.playerError = !useFallback;
        this.playerErrorMessage = message;
    },

    refreshStream() {
        if (!this.activeVariant) return;
        this.fallbackActive = false;
        this.attemptedVariantIds = [];
        this.playVariant(this.activeVariant, false);
        this.toast(t('Stream refreshed'));
    },

    stepChannel(direction) {
        const groups = this.filteredGroups;
        if (!groups.length) return;
        const current = groups.findIndex((group) => group.id === this.activeGroup?.id);
        const index = (Math.max(current, 0) + direction + groups.length) % groups.length;
        this.watchGroup(groups[index]);
    },

    stepQuality(direction) {
        const variants = this.activeGroup?.variants || [];
        if (variants.length < 2) return;
        const current = variants.findIndex((variant) => variant.id === this.activeVariant?.id);
        const index = (Math.max(current, 0) + direction + variants.length) % variants.length;
        this.attemptedVariantIds = [];
        this.playVariant(variants[index]);
    },

    toggleFavorite() {
        if (!this.activeGroup) return;
        if (this.isFavorite) {
            this.favorites = this.favorites.filter((id) => id !== this.activeGroup.id);
            this.toast(t('Removed from favorites'));
        } else {
            this.favorites = [this.activeGroup.id, ...this.favorites];
            this.toast(t('Added to favorites'));
        }
        writeStorage(STORAGE.favorites, this.favorites);
    },

    rememberRecent(groupId) {
        this.recent = [groupId, ...this.recent.filter((id) => id !== groupId)].slice(0, 30);
        writeStorage(STORAGE.recent, this.recent);
    },

    setCategory(category) {
        this.activeCategory = category;
        this.focusedIndex = 0;
    },

    categoryLabel(category) {
        return t(category);
    },

    openExternalPlayer() {
        if (this.externalPlayerUrl) window.open(this.externalPlayerUrl, '_blank', 'noopener');
    },

    fullscreen() {
        this.$refs.playerStage?.requestFullscreen?.();
    },

    toast(message) {
        this.toastMessage = message;
        clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 2800);
    },

    handleKeydown(event) {
        const editing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
        if (event.key === '/') {
            event.preventDefault();
            this.$refs.search?.focus();
            return;
        }
        if (event.key === 'Escape' && editing) {
            this.search = '';
            document.activeElement.blur();
            return;
        }
        if (editing) return;
        if (event.key === 'ArrowUp') { event.preventDefault(); this.stepChannel(-1); }
        if (event.key === 'ArrowDown') { event.preventDefault(); this.stepChannel(1); }
        if (event.key === 'ArrowLeft') { event.preventDefault(); this.stepQuality(-1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); this.stepQuality(1); }
        if (event.key.toLowerCase() === 'f') { event.preventDefault(); this.toggleFavorite(); }
        if (event.key === 'Enter' && this.filteredGroups[this.focusedIndex]) {
            this.watchGroup(this.filteredGroups[this.focusedIndex]);
        }
    },
});

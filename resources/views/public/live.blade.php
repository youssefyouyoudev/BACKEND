@extends('layouts.app')

@section('title', 'Live TV Channels | RifiMedia')
@section('description', 'Watch approved live TV channels from public playlists with search, categories, and a mobile-friendly player.')

@php
    $recommendedChannels = collect($initialChannels)->take(8)->map(fn ($channel) => [
        'id' => $channel['id'],
        'name' => $channel['name'],
        'original_name' => $channel['original_name'] ?? $channel['name'],
        'display_tags' => $channel['display_tags'] ?? [],
        'quality_label' => $channel['quality_label'] ?? 'HD',
        'avatar' => $channel['logo'],
        'category' => $channel['group_title'],
        'viewers_label' => $channel['viewers_label'],
    ]);
@endphp

@section('content')
<div
    class="rm-page rm-page--live"
    x-data="liveTvPage({
        initialChannels: @js($initialChannels),
        initialChannelId: @js(request()->integer('channel') ?: null),
        categories: @js($categoryCounts),
    })"
    x-init="init"
>
    <section class="rm-live-stage" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <div class="rm-live-stage__header">
            <div>
                <span class="rm-live-badge"><i></i> <x-icon name="signal" /> Live TV</span>
                <h1>Live TV channels with a cinematic player.</h1>
                <p>Start watching instantly, search by channel name, filter by category, and jump into a full player whenever you need more space.</p>
                <div class="rm-hero-microstats" aria-label="Live TV highlights">
                    <span><x-icon name="tv" /> {{ number_format($totalCount) }} channels</span>
                    <span><x-icon name="search" /> Search and filters</span>
                    <span><x-icon name="play" /> Full player links</span>
                </div>
            </div>

            <form class="rm-search rm-search--wide" @submit.prevent="loadChannels">
                <x-icon name="search" />
                <input type="search" x-model.debounce.400ms="search" @input.debounce.450ms="loadChannels" placeholder="Search live channels">
            </form>
        </div>

        <div class="rm-live-loading-layout" x-show.important="loadingList && channels.length === 0" x-cloak aria-live="polite" aria-label="Loading live channels">
            <section class="rm-player-shell rm-player-shell--skeleton">
                <div class="rm-player-header rm-player-header--skeleton">
                    <span></span>
                    <div><strong></strong><p></p></div>
                </div>
                <div class="rm-player-frame rm-player-frame--spa rm-player-frame--skeleton"></div>
            </section>
            <aside class="rm-glass-card rm-live-browser rm-live-browser--skeleton">
                <div class="rm-live-browser__top"><strong>Loading channels</strong></div>
                <div class="rm-skeleton-list" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span>
                </div>
            </aside>
        </div>

        <section class="rm-live-state-panel rm-live-state-panel--error" x-show.important="!loadingList && loadError && channels.length === 0" x-cloak aria-live="polite">
            <span class="rm-live-state-panel__icon"><x-icon name="signal" /></span>
            <p class="rm-eyebrow">Channel service unavailable</p>
            <h2>Channels could not be loaded.</h2>
            <p>Check your connection and try again. Your current playlist and watch links are unchanged.</p>
            <button type="button" class="rm-btn rm-btn-primary" @click="loadChannels"><x-icon name="signal" />Retry</button>
        </section>

        <div class="rm-live-fallback-note" x-show.important="!loadingList && loadError && channels.length > 0" x-cloak role="status">
            <span><x-icon name="signal" /></span>
            <strong>Showing cached channels.</strong>
            <p>The live channel API did not respond, but your current list is still available.</p>
            <button type="button" class="rm-btn rm-btn-secondary rm-btn-sm" @click="loadChannels">Retry</button>
        </div>

        <div class="rm-live-layout" id="channels" x-show.important="channels.length > 0 || (!loadingList && !loadError)" x-cloak>
            <aside class="rm-glass-card rm-live-browser" aria-label="Live channel browser">
                <div class="rm-live-browser__top">
                    <strong>Channels</strong>
                    <button type="button" class="rm-icon-btn" @click="loadChannels" aria-label="Refresh channels">
                        <x-icon name="signal" />
                    </button>
                </div>

                <div class="rm-tabs chip-scroll" role="tablist" aria-label="Filter channels">
                    <button type="button" class="chip" :class="{ 'is-active chip-active': activeCategory === '__ALL__' }" @click="setCategory('__ALL__')">
                        All <span>{{ number_format($totalCount) }}</span>
                    </button>
                    @foreach($categoryCounts->take(8) as $category => $count)
                        <button type="button" class="chip" :class="{ 'is-active chip-active': activeCategory === @js($category) }" @click="setCategory(@js($category))">
                            {{ $category }} <span>{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="rm-live-list">
                    <template x-for="channel in channels" :key="channel.id">
                        <button type="button" class="rm-live-row" :class="{ 'is-active': activeChannel && activeChannel.id === channel.id }" @click="selectChannel(channel.id)">
                            <img :src="channel.logo || fallbackLogo" :alt="channel.name" loading="lazy" x-on:error="$event.target.src = fallbackLogo">
                            <span>
                                <strong x-text="channel.name || 'Live channel'"></strong>
                                <small>
                                    <span x-text="channel.group_title || 'Live TV'"></span>
                                    <span aria-hidden="true"> • </span>
                                    <span x-text="channel.language_label || 'Global'"></span>
                                    <span aria-hidden="true"> • </span>
                                    <span x-text="channel.quality_label || 'HD'"></span>
                                </small>
                            </span>
                            <b aria-hidden="true" x-text="channel.status_label || 'On air'"></b>
                            <em x-show.important="channel.viewers_label"><i></i><span x-text="channel.viewers_label"></span></em>
                        </button>
                    </template>

                    <div class="rm-empty-state rm-empty-state--compact" x-show.important="channelsLoaded && channels.length === 0">
                        <span x-text="emptyTitle"></span>
                        <strong x-text="emptyMessage"></strong>
                    </div>
                </div>
            </aside>

            <section class="rm-player-shell">
                <div class="rm-player-header" x-show.important="activeChannel">
                    <span class="rm-live-badge"><i></i> <x-icon name="play" /> Live</span>
                    <div>
                        <h2 x-text="selectedTitle"></h2>
                        <p x-show.important="selectedMeta" x-text="selectedMeta"></p>
                    </div>
                    <a :href="activeChannel ? `/watch/${activeChannel.id}` : '#'" class="rm-btn rm-btn-primary rm-btn-sm"><x-icon name="play" />Full Player</a>
                </div>

                <div class="rm-player-header rm-player-header--empty" x-show.important="!activeChannel">
                    <span class="rm-live-badge rm-live-badge--idle"><x-icon name="tv" /> Ready</span>
                    <div>
                        <h2>Choose a channel to start watching.</h2>
                        <p>Pick any channel from the list to load the player.</p>
                    </div>
                </div>

                <div class="rm-player-frame rm-player-frame--spa" :class="{ 'is-empty': !activeChannel }">
                    <video x-ref="video" controls playsinline autoplay muted></video>
                    <div class="rm-player-empty" x-show.important="!activeChannel">
                        <span><x-icon name="tv" /></span>
                        <strong>Choose a channel to start watching.</strong>
                        <p>Search or use the category filters to find a live channel.</p>
                    </div>
                    <div class="rm-player-loading" x-show.important="loadingPlayer" x-transition.opacity>
                        <span></span>
                        <p>Connecting to selected channel...</p>
                    </div>
                    <div class="rm-player-empty rm-player-empty--error" x-show.important="playerError && activeChannel && !loadingPlayer">
                        <span><x-icon name="signal" /></span>
                        <strong>This channel could not be loaded.</strong>
                        <p x-text="playerErrorMessage || 'Try again, or choose another channel from the list.'"></p>
                        <div class="rm-empty-state__actions">
                            <button type="button" class="rm-btn rm-btn-secondary rm-btn-sm" @click="selectChannel(activeChannel.id)">Retry</button>
                            <a x-show.important="externalPlayerUrl" :href="externalPlayerUrl || '#'" class="rm-btn rm-btn-primary rm-btn-sm" target="_blank" rel="noopener">Open external player</a>
                        </div>
                    </div>
                </div>

                <div class="rm-glass-card rm-live-details" x-show.important="activeChannel">
                    <img :src="activeChannel?.logo || fallbackLogo" alt="" loading="lazy" x-on:error="$event.target.src = fallbackLogo">
                    <div>
                        <p class="rm-eyebrow">Now streaming</p>
                        <h3 x-text="activeChannel?.name"></h3>
                        <p x-text="activeChannel?.description"></p>
                    </div>
                </div>
            </section>
        </div>
    </section>

    @if($recommendedChannels->count())
        <section class="rm-section" id="following" x-show.important="!loadingList && !loadError && channelsLoaded && channels.length > 0" x-cloak>
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Recommended</p>
                    <h2>More live channels</h2>
                </div>
            </div>
            <div class="rm-match-row">
                @foreach($recommendedChannels as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('liveTvPage', ({ initialChannels, initialChannelId }) => ({
        channels: initialChannels || [],
        activeChannel: null,
        activeCategory: '__ALL__',
        search: '',
        loadingList: false,
        loadingPlayer: false,
        channelsLoaded: Array.isArray(initialChannels),
        playerError: false,
        playerErrorMessage: '',
        externalPlayerUrl: '',
        fallbackLogo: @js(asset('brand/rifi-logo.png')),
        hls: null,
        mpegts: null,
        previewReconnects: 0,
        loadError: false,

        get selectedTitle() {
            return this.activeChannel?.name || '';
        },

        get selectedMeta() {
            if (!this.activeChannel) return '';

            const parts = [
                this.activeChannel.group_title || this.activeChannel.category,
                this.activeChannel.viewers_label ? `${this.activeChannel.viewers_label} watching` : '',
            ].filter(Boolean);

            return parts.join(' | ');
        },

        get emptyTitle() {
            return this.search.trim() || this.activeCategory !== '__ALL__'
                ? 'No channels match your search'
                : 'No live channels available right now';
        },

        get emptyMessage() {
            return this.search.trim() || this.activeCategory !== '__ALL__'
                ? 'Try another keyword or category.'
                : 'Approved live channels will appear here when they are available.';
        },

        init() {
            const requested = initialChannelId
                ? this.channels.find((channel) => Number(channel.id) === Number(initialChannelId))
                : this.channels[0];

            if (requested) {
                this.selectChannel(requested.id);
            }
        },

        async setCategory(category) {
            this.activeCategory = category;
            await this.loadChannels();
        },

        async loadChannels() {
            this.loadingList = true;
            this.loadError = false;
            const params = new URLSearchParams({
                per_page: '80',
                category: this.activeCategory,
                search: this.search,
            });

            try {
                const response = await fetch(`/api/tv/channels?${params}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Could not load channels');
                const payload = await response.json();
                this.channels = payload.data || [];
                this.channelsLoaded = true;
                if (this.channels.length === 0) {
                    this.clearActiveChannel();
                    return;
                }
                if (this.channels.length && (!this.activeChannel || !this.channels.some((channel) => channel.id === this.activeChannel.id))) {
                    await this.selectChannel(this.channels[0].id);
                }
            } catch (error) {
                this.loadError = true;
                this.channelsLoaded = true;
            } finally {
                this.loadingList = false;
            }
        },

        clearActiveChannel() {
            this.activeChannel = null;
            this.loadingPlayer = false;
            this.playerError = false;
            this.playerErrorMessage = '';
            this.externalPlayerUrl = '';

            const video = this.$refs.video;
            if (video) {
                video.pause();
                video.removeAttribute('src');
                video.load();
            }

            if (this.hls) {
                this.hls.destroy();
                this.hls = null;
            }

            if (this.mpegts) {
                this.mpegts.unload();
                this.mpegts.detachMediaElement();
                this.mpegts.destroy();
                this.mpegts = null;
            }
        },

        async selectChannel(id) {
            this.loadingPlayer = true;
            this.playerError = false;
            this.playerErrorMessage = '';
            const cached = this.channels.find((channel) => Number(channel.id) === Number(id));
            if (cached) this.activeChannel = cached;

            try {
                const response = await fetch(`/api/tv/channels/${id}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Could not load stream');
                const payload = await response.json();
                this.activeChannel = payload.data;
                this.playSource((payload.data.sources || [])[0]);
                history.replaceState(null, '', `{{ route('live-tv') }}?channel=${id}`);
            } catch (error) {
                this.activeChannel = cached || null;
                this.loadingPlayer = false;
                this.playerError = true;
                this.playerErrorMessage = 'The stream details could not be loaded. Your channel list is still available.';
            }
        },

        playSource(source) {
            const video = this.$refs.video;
            if (!source || !video) {
                this.loadingPlayer = false;
                this.playerError = true;
                this.playerErrorMessage = 'No playable stream source is configured for this channel.';
                return;
            }
            this.playerError = false;
            this.playerErrorMessage = '';
            this.externalPlayerUrl = source.external_url || source.url || '';
            this.previewReconnects = 0;

            if (source.requires_external_player && window.location.protocol === 'https:') {
                this.loadingPlayer = false;
                this.playerError = true;
                this.playerErrorMessage = 'This HTTP-only stream cannot play inside an HTTPS page. Open it in an external player or choose another source.';
                return;
            }

            if (this.hls) {
                this.hls.destroy();
                this.hls = null;
            }

            if (this.mpegts) {
                this.mpegts.unload();
                this.mpegts.detachMediaElement();
                this.mpegts.destroy();
                this.mpegts = null;
            }

            const type = String(source.type || '').toLowerCase();
            const isHls = type === 'hls' || String(source.url).toLowerCase().includes('.m3u');
            const isMpegTs = ['mpegts', 'ts', 'stream'].includes(type);
            const markPlaying = () => {
                this.loadingPlayer = false;
                this.previewReconnects = 0;
            };
            const softReconnect = () => {
                if (!isHls && !isMpegTs) return;
                if (this.previewReconnects >= 3) {
                    this.loadingPlayer = false;
                    return;
                }
                this.previewReconnects += 1;
                this.loadingPlayer = true;
                setTimeout(() => {
                    video.play().catch(() => {
                        if (this.hls) this.hls.startLoad();
                        if (this.mpegts) {
                            try {
                                this.mpegts.unload();
                                this.mpegts.load();
                                this.mpegts.play();
                            } catch (error) {
                                console.error('[RiFiPlayer] live preview reconnect failed', error);
                            }
                        }
                    });
                }, [1000, 3000, 5000][this.previewReconnects - 1] || 5000);
            };

            video.onplaying = markPlaying;
            video.ontimeupdate = markPlaying;
            video.onended = softReconnect;
            video.onwaiting = () => {
                if (this.previewReconnects === 0) setTimeout(() => {
                    if (video.readyState < 3) this.loadingPlayer = true;
                }, 2000);
            };
            video.onstalled = video.onwaiting;

            if (isHls && window.Hls && Hls.isSupported()) {
                this.hls = new Hls({
                    liveSyncDurationCount: 6,
                    liveMaxLatencyDurationCount: 12,
                    maxBufferLength: 60,
                    maxMaxBufferLength: 120,
                    backBufferLength: 60,
                    enableWorker: true,
                    lowLatencyMode: false,
                });
                this.hls.loadSource(source.url);
                this.hls.attachMedia(video);
                this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    markPlaying();
                    video.play().catch(() => {});
                });
                this.hls.on(Hls.Events.ERROR, (_, data) => {
                    if (!data.fatal) return;
                    if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                        this.hls.startLoad();
                        softReconnect();
                        return;
                    }
                    if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                        this.hls.recoverMediaError();
                        softReconnect();
                        return;
                    }
                    softReconnect();
                });
                return;
            }

            if (isMpegTs && window.mpegts?.isSupported()) {
                this.mpegts = window.mpegts.createPlayer({
                    type: 'mpegts',
                    isLive: true,
                    url: source.url,
                    cors: true,
                    withCredentials: false,
                }, {
                    enableWorker: true,
                    lazyLoad: false,
                    stashInitialSize: 1024,
                    liveBufferLatencyChasing: false,
                    autoCleanupSourceBuffer: true,
                    autoCleanupMaxBackwardDuration: 60,
                    autoCleanupMinBackwardDuration: 30,
                    fixAudioTimestampGap: true,
                    accurateSeek: false,
                });
                this.mpegts.attachMediaElement(video);
                this.mpegts.on(window.mpegts.Events.ERROR, (errorType, detail) => {
                    console.error('[RiFiPlayer] mpegts.js live page error', { errorType, detail });
                    softReconnect();
                });
                video.addEventListener('loadedmetadata', () => {
                    video.play().catch(() => {});
                }, { once: true });
                this.mpegts.load();
                return;
            }

            video.src = source.url;
            video.oncanplay = () => {
                this.loadingPlayer = false;
                video.play().catch(() => {});
            };
            video.load();
        },
    }));
});
</script>
@endpush
@endsection

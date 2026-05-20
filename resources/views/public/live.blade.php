@extends('layouts.app')

@php
    $recommendedChannels = collect($initialChannels)->take(8)->map(fn ($channel) => [
        'id' => $channel['id'],
        'name' => $channel['name'],
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
    <section class="rm-live-stage">
        <div class="rm-live-stage__header">
            <div>
                <span class="rm-live-badge"><i></i> Live TV</span>
                <h1>Sports channels, instantly switchable.</h1>
                <p>Browse approved public streams with fast search, category filters, and a premium full-screen player path.</p>
            </div>

            <form class="rm-search rm-search--wide" @submit.prevent="loadChannels">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input type="search" x-model.debounce.400ms="search" @input.debounce.450ms="loadChannels" placeholder="Search live channels">
            </form>
        </div>

        <div class="rm-live-layout">
            <aside class="rm-glass-card rm-live-browser" aria-label="Live channel browser">
                <div class="rm-live-browser__top">
                    <strong>Channels</strong>
                    <button type="button" class="rm-icon-btn" @click="loadChannels" aria-label="Refresh channels">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M20 6v5h-5"></path><path d="M4 18v-5h5"></path><path d="M18 9a7 7 0 0 0-11.8-2.4M6 15a7 7 0 0 0 11.8 2.4"></path></svg>
                    </button>
                </div>

                <div class="rm-tabs" role="tablist" aria-label="Filter channels">
                    <button type="button" :class="{ 'is-active': activeCategory === '__ALL__' }" @click="setCategory('__ALL__')">
                        All <span>{{ number_format($totalCount) }}</span>
                    </button>
                    @foreach($categoryCounts->take(8) as $category => $count)
                        <button type="button" :class="{ 'is-active': activeCategory === @js($category) }" @click="setCategory(@js($category))">
                            {{ $category }} <span>{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="rm-live-list">
                    <template x-for="channel in channels" :key="channel.id">
                        <button type="button" class="rm-live-row" :class="{ 'is-active': activeChannel && activeChannel.id === channel.id }" @click="selectChannel(channel.id)">
                            <img :src="channel.logo || fallbackLogo" :alt="channel.name" loading="lazy" x-on:error="$event.target.src = fallbackLogo">
                            <span>
                                <strong x-text="channel.name"></strong>
                                <small x-text="channel.group_title"></small>
                            </span>
                            <em><i></i><span x-text="channel.viewers_label"></span></em>
                        </button>
                    </template>

                    <div class="rm-empty-state rm-empty-state--compact" x-show="!loadingList && channels.length === 0">
                        <span>No channels found</span>
                        <strong>Try a different search or category.</strong>
                    </div>
                </div>
            </aside>

            <section class="rm-player-shell">
                <div class="rm-player-header" x-show="activeChannel">
                    <span class="rm-live-badge"><i></i> Live</span>
                    <div>
                        <h2 x-text="activeChannel?.name || 'Select a channel'"></h2>
                        <p><span x-text="activeChannel?.group_title"></span> <span aria-hidden="true">-</span> <strong x-text="activeChannel?.viewers_label"></strong> watching</p>
                    </div>
                    <a :href="activeChannel ? `/watch/${activeChannel.id}` : '#'" class="rm-btn rm-btn-primary rm-btn-sm">Full Player</a>
                </div>

                <div class="rm-player-frame rm-player-frame--spa">
                    <video x-ref="video" controls playsinline autoplay muted></video>
                    <div class="rm-player-loading" x-show="loadingPlayer" x-transition.opacity>
                        <span></span>
                        <p>Connecting to selected channel...</p>
                    </div>
                </div>

                <div class="rm-glass-card rm-live-details" x-show="activeChannel">
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
        <section class="rm-section" id="following">
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
        fallbackLogo: @js(asset('brand/rifi-logo.png')),
        hls: null,
        mpegts: null,

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
                if (this.channels.length && (!this.activeChannel || !this.channels.some((channel) => channel.id === this.activeChannel.id))) {
                    await this.selectChannel(this.channels[0].id);
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.loadingList = false;
            }
        },

        async selectChannel(id) {
            this.loadingPlayer = true;
            const cached = this.channels.find((channel) => Number(channel.id) === Number(id));
            if (cached) this.activeChannel = cached;

            try {
                const response = await fetch(`/api/tv/channels/${id}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Could not load stream');
                const payload = await response.json();
                this.activeChannel = payload.data;
                this.playSource((payload.data.sources || [])[0]);
                history.replaceState(null, '', `{{ route('live') }}?channel=${id}`);
            } catch (error) {
                console.error(error);
                this.loadingPlayer = false;
            }
        },

        playSource(source) {
            const video = this.$refs.video;
            if (!source || !video) {
                this.loadingPlayer = false;
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

            if (isHls && window.Hls && Hls.isSupported()) {
                this.hls = new Hls({ lowLatencyMode: true, backBufferLength: 30 });
                this.hls.loadSource(source.url);
                this.hls.attachMedia(video);
                this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    this.loadingPlayer = false;
                    video.play().catch(() => {});
                });
                this.hls.on(Hls.Events.ERROR, (_, data) => {
                    if (data.fatal) this.loadingPlayer = false;
                });
                return;
            }

            if (isMpegTs && window.mpegts?.isSupported()) {
                this.mpegts = window.mpegts.createPlayer({
                    type: 'mpegts',
                    isLive: true,
                    url: source.url,
                }, {
                    enableWorker: true,
                    lazyLoad: false,
                    liveBufferLatencyChasing: true,
                });
                this.mpegts.attachMediaElement(video);
                this.mpegts.on(window.mpegts.Events.ERROR, (errorType, detail) => {
                    console.error('[RiFiPlayer] mpegts.js live page error', { errorType, detail });
                    this.loadingPlayer = false;
                });
                video.addEventListener('loadedmetadata', () => {
                    this.loadingPlayer = false;
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

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
    class="streaming-app streaming-app--live"
    x-data="liveTvPage({
        initialChannels: @js($initialChannels),
        initialChannelId: @js(request()->integer('channel') ?: null),
        categories: @js($categoryCounts),
    })"
    x-init="init"
>
    <x-streaming.sidebar active="live" :recommended-channels="$recommendedChannels" />

    <main class="stream-main">
        <header class="stream-topbar">
            <div class="browse-select">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <span>Live TV</span>
                <small>{{ number_format($totalCount) }}</small>
            </div>

            <form class="stream-search" @submit.prevent="loadChannels">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" x-model.debounce.400ms="search" @input.debounce.450ms="loadChannels" placeholder="Search live channels">
            </form>

            <div class="stream-actions">
                <button type="button" @click="loadChannels" title="Refresh" aria-label="Refresh">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M20 6v5h-5"/><path d="M4 18v-5h5"/><path d="M18 9a7 7 0 0 0-11.8-2.4M6 15a7 7 0 0 0 11.8 2.4"/></svg>
                </button>
                <button type="button" title="Notifications" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                </button>
                <a href="{{ route('admin.login') }}" class="profile-pill" aria-label="Profile">
                    <span></span>
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.6-4 4.2-6 8-6s6.4 2 8 6"/></svg>
                </a>
            </div>
        </header>

        <div class="live-shell">
            <aside class="live-left-panel">
                <div class="live-filter-row">
                    <button type="button" :class="{ 'is-active': activeCategory === '__ALL__' }" @click="setCategory('__ALL__')">
                        All <span>{{ number_format($totalCount) }}</span>
                    </button>
                    @foreach($categoryCounts->take(8) as $category => $count)
                        <button type="button" :class="{ 'is-active': activeCategory === @js($category) }" @click="setCategory(@js($category))">
                            {{ $category }} <span>{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="live-channel-scroll">
                    <template x-for="channel in channels" :key="channel.id">
                        <button type="button" class="live-channel-row" :class="{ 'is-active': activeChannel && activeChannel.id === channel.id }" @click="selectChannel(channel.id)">
                            <img :src="channel.logo" alt="" loading="lazy">
                            <span>
                                <strong x-text="channel.name"></strong>
                                <small x-text="channel.group_title"></small>
                            </span>
                            <em><i></i><span x-text="channel.viewers_label"></span></em>
                        </button>
                    </template>

                    <div class="live-list-empty" x-show="!loadingList && channels.length === 0">
                        No channels found.
                    </div>
                </div>
            </aside>

            <section class="live-player-panel">
                <div class="live-video-frame">
                    <video x-ref="video" controls playsinline autoplay muted></video>
                    <div class="spa-loading" x-show="loadingPlayer" x-transition.opacity>
                        <span></span>
                        <p>Connecting to selected channel...</p>
                    </div>
                </div>

                <div class="live-info-row" x-show="activeChannel">
                    <img :src="activeChannel?.logo" alt="" loading="lazy">
                    <div>
                        <p class="live-badge">Live</p>
                        <h1 x-text="activeChannel?.name"></h1>
                        <span x-text="activeChannel?.description"></span>
                        <small><strong x-text="activeChannel?.viewers_label"></strong> viewers | <span x-text="activeChannel?.group_title"></span></small>
                    </div>
                    <a :href="activeChannel ? `/watch/${activeChannel.id}` : '#'" class="watch-button">Full Player</a>
                </div>

                <aside class="chat-panel chat-panel--live" aria-label="Chat section">
                    <h3>Chat</h3>
                    <p><strong>System:</strong> Channel switching is running through JSON endpoints.</p>
                    <p><strong>Moderator:</strong> No page reloads needed.</p>
                    <form @submit.prevent>
                        <input type="text" placeholder="Write a message">
                        <button type="submit">Send</button>
                    </form>
                </aside>
            </section>
        </div>
    </main>
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
        hls: null,

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

            if (window.Hls && Hls.isSupported() && String(source.url).includes('.m3u')) {
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

@extends('layouts.app')

@section('content')
<div
    class="clean-watch"
    x-data="cleanWatchPlayer({
        channels: @js($channelList),
        active: @js($activeChannel),
    })"
    x-init="init"
>
    <aside class="clean-watch__list" aria-label="Channels">
        <div class="clean-watch__brand">
            <x-logo compact />
        </div>

        <div class="clean-watch__search">
            <input type="search" x-model.debounce.250ms="search" placeholder="Search channels">
        </div>

        <div class="clean-watch__items">
            <template x-for="channel in filteredChannels" :key="channel.id">
                <button
                    type="button"
                    class="clean-channel"
                    :class="{ 'is-active': activeChannel && activeChannel.id === channel.id }"
                    @click="switchChannel(channel.id)"
                >
                    <img :src="channel.logo" :alt="channel.name" loading="lazy" x-on:error="$event.target.src='{{ asset('brand/rifi-logo.png') }}'">
                    <span>
                        <strong x-text="channel.name"></strong>
                        <small x-text="channel.category"></small>
                    </span>
                    <i aria-hidden="true"></i>
                </button>
            </template>
        </div>
    </aside>

    <main class="clean-watch__player">
        <section class="clean-player-shell" :class="{ 'is-switching': switching }">
            <video x-ref="video" controls autoplay muted playsinline></video>
            <div class="clean-player-loading" x-show="loading" x-transition.opacity>
                <span></span>
            </div>
        </section>

        <section class="clean-channel-meta" x-show="activeChannel" x-transition.opacity>
            <div>
                <h1 x-text="activeChannel?.name"></h1>
                <p>
                    <span x-text="activeChannel?.category"></span>
                    <template x-if="activeChannel?.description">
                        <em x-text="activeChannel.description"></em>
                    </template>
                </p>
            </div>
            <a href="{{ route('home') }}">Home</a>
        </section>
    </main>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cleanWatchPlayer', ({ channels, active }) => ({
        channels: channels || [],
        activeChannel: active || null,
        search: '',
        loading: false,
        switching: false,
        hls: null,

        get filteredChannels() {
            const query = this.search.trim().toLowerCase();
            if (!query) return this.channels;

            return this.channels.filter((channel) =>
                channel.name.toLowerCase().includes(query)
                || channel.category.toLowerCase().includes(query)
            );
        },

        init() {
            if (this.activeChannel) {
                this.play(this.activeChannel);
            }
        },

        async switchChannel(id) {
            if (this.activeChannel && Number(this.activeChannel.id) === Number(id)) return;

            this.loading = true;
            this.switching = true;

            const cached = this.channels.find((channel) => Number(channel.id) === Number(id));
            if (cached) this.activeChannel = cached;

            try {
                const response = await fetch(`/api/channels/${id}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Channel could not be loaded.');

                const payload = await response.json();
                this.activeChannel = payload.data;
                this.play(payload.data);
                history.replaceState(null, '', `/watch/${id}`);
            } catch (error) {
                console.error(error);
                this.loading = false;
                this.switching = false;
            }
        },

        play(channel) {
            const video = this.$refs.video;
            if (!video || !channel?.stream_url) {
                this.loading = false;
                this.switching = false;
                return;
            }

            if (this.hls) {
                this.hls.destroy();
                this.hls = null;
            }

            const finish = () => {
                this.loading = false;
                setTimeout(() => { this.switching = false; }, 180);
                video.play().catch(() => {});
            };

            if (window.Hls && Hls.isSupported() && String(channel.stream_url).includes('.m3u')) {
                this.hls = new Hls({ lowLatencyMode: true, backBufferLength: 30 });
                this.hls.loadSource(channel.stream_url);
                this.hls.attachMedia(video);
                this.hls.on(Hls.Events.MANIFEST_PARSED, finish);
                this.hls.on(Hls.Events.ERROR, (_, data) => {
                    if (data.fatal) finish();
                });
                return;
            }

            video.src = channel.stream_url;
            video.oncanplay = finish;
            video.load();
        },
    }));
});
</script>
@endpush
@endsection

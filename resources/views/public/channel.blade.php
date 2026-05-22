@extends('layouts.app')

@section('title', ($activeChannel['name'] ?? 'Channel').' Information | RifiMedia Sports')
@section('description', 'Channel information, program guide, related media, and permitted live player access on RifiMedia Sports.')
@section('robots', 'noindex,follow')

@section('content')
<div
    class="rm-page rm-page--watch"
    x-data="satWatchPage({
        activeId: @js($activeChannel['id'] ?? null),
        channels: @js($channelList),
    })"
    x-init="init"
>
    <section class="rm-watch-shell">
        <main class="rm-watch-main">
            <header class="rm-player-header rm-player-header--hero">
                <span class="rm-live-badge"><i></i> Live</span>
                <div>
                    <p class="rm-eyebrow">{{ $activeChannel['category'] ?? 'Live TV' }}</p>
                    <h1>{{ $activeChannel['name'] }}</h1>
                    <p>{{ $activeChannel['description'] }}</p>
                </div>
                <div class="rm-player-header__actions">
                    <a class="rm-btn rm-btn-secondary rm-btn-sm" href="{{ route('live-tv') }}">Back to Live TV</a>
                    <a class="rm-btn rm-btn-primary rm-btn-sm" href="{{ route('sports.football') }}">Football Scores</a>
                </div>
            </header>

            <section class="rm-player-shell" data-sticky-player>
                <x-video-player
                    :channel="$activeChannel"
                    :sources="$sources"
                    :poster="$activeChannel['logo'] ?? null"
                />
            </section>

            <section class="rm-glass-card rm-match-overview">
                <div>
                    <span class="rm-live-badge rm-live-badge--small"><i></i> Now watching</span>
                    <h2>{{ $activeChannel['name'] }}</h2>
                    @if(! empty($activeChannel['program']))
                        <p>{{ $activeChannel['program']['title'] }} - {{ $activeChannel['program']['start_time'] }} to {{ $activeChannel['program']['end_time'] }}</p>
                    @else
                        <p>Live broadcast - program guide updates will appear here when published.</p>
                    @endif
                </div>
                <div class="rm-server-card">
                    <span>Stream quality</span>
                    <strong>Auto HD</strong>
                    <small>{{ count($sources) }} {{ count($sources) === 1 ? 'server' : 'servers' }} available</small>
                </div>
            </section>

            <section class="rm-section rm-section--flush">
                <div class="rm-section-header">
                    <div>
                        <p class="rm-eyebrow">Electronic program guide</p>
                        <h2>Up next</h2>
                    </div>
                </div>

                @if($programs->count())
                    <div class="rm-schedule-list">
                        @foreach($programs as $program)
                            <article class="{{ $program->start_time <= now() && $program->end_time > now() ? 'is-now' : '' }}">
                                <time>{{ $program->start_time->format('H:i') }} - {{ $program->end_time->format('H:i') }}</time>
                                <div>
                                    <strong>{{ $program->title }}</strong>
                                    <p>{{ $program->description ?: 'Live programming from this channel.' }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rm-empty-state">
                        <span>No schedule yet</span>
                        <strong>This channel is live, but no program guide has been published.</strong>
                    </div>
                @endif
            </section>
        </main>

        <x-sidebar :channels="$channelList" :active-id="$activeChannel['id'] ?? null" />
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('satWatchPage', ({ activeId, channels }) => ({
        activeId,
        channels: channels || [],
        init() {
            window.addEventListener('rifi:player-next', () => this.goNext());
            window.addEventListener('keydown', (event) => this.handleKeys(event));
            window.addEventListener('scroll', () => {
                document.body.classList.toggle('has-mini-player', window.scrollY > 420);
            }, { passive: true });
        },
        goNext() {
            const index = this.channels.findIndex((channel) => Number(channel.id) === Number(this.activeId));
            const next = this.channels[index + 1] || this.channels[0];
            if (next) window.location.href = `/watch/${next.id}`;
        },
        handleKeys(event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
                event.preventDefault();
                this.goNext();
            }
            if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                event.preventDefault();
                const index = this.channels.findIndex((channel) => Number(channel.id) === Number(this.activeId));
                const previous = this.channels[index - 1] || this.channels[this.channels.length - 1];
                if (previous) window.location.href = `/watch/${previous.id}`;
            }
        },
    }));
});
</script>
@endpush
@endsection

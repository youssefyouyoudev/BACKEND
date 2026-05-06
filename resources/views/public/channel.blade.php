@extends('layouts.app')

@section('content')
<div
    class="sat-watch"
    x-data="satWatchPage({
        activeId: @js($activeChannel['id'] ?? null),
        channels: @js($channelList),
    })"
    x-init="init"
>
    <x-sidebar :channels="$channelList" :active-id="$activeChannel['id'] ?? null" />

    <main class="sat-watch__main">
        <section class="sat-watch__player" data-sticky-player>
            <x-video-player
                :channel="$activeChannel"
                :sources="$sources"
                :poster="$activeChannel['logo'] ?? null"
            />
        </section>

        <section class="sat-info-panel">
            <div>
                <span class="sat-kicker"><i></i> Now watching</span>
                <h1>{{ $activeChannel['name'] }}</h1>
                <p>{{ $activeChannel['description'] }}</p>
                @if(! empty($activeChannel['program']))
                    <strong>{{ $activeChannel['program']['title'] }} · {{ $activeChannel['program']['start_time'] }} - {{ $activeChannel['program']['end_time'] }}</strong>
                @else
                    <strong>Live broadcast · Schedule coming soon</strong>
                @endif
            </div>
            <div class="sat-info-panel__actions">
                <a class="sat-button sat-button--ghost" href="{{ route('home') }}">Channel wall</a>
                <a class="sat-button sat-button--primary" href="{{ route('live') }}">TV guide</a>
            </div>
        </section>

        <section class="sat-epg">
            <div class="sat-section__heading">
                <div>
                    <span>Electronic program guide</span>
                    <h2>Up next</h2>
                </div>
            </div>
            @if($programs->count())
                <div class="sat-epg__timeline">
                    @foreach($programs as $program)
                        <article class="{{ $program->start_time <= now() && $program->end_time > now() ? 'is-now' : '' }}">
                            <time>{{ $program->start_time->format('H:i') }} - {{ $program->end_time->format('H:i') }}</time>
                            <strong>{{ $program->title }}</strong>
                            <p>{{ $program->description ?: 'Live programming from this channel.' }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="sat-empty">
                    <strong>No EPG data yet</strong>
                    <p>The channel is live, but no program schedule has been published.</p>
                </div>
            @endif
        </section>
    </main>
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

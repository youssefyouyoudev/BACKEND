@extends('layouts.app')

@section('content')
<div class="sat-home" x-data="satHomePage()" x-init="init">
    <header class="sat-topbar">
        <x-logo />
        <form action="{{ route('home') }}" method="GET" class="sat-search">
            <span></span>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search live channels, sports, news, movies">
        </form>
        <nav class="sat-topbar__links">
            <a href="{{ route('live') }}">Live TV</a>
            <a href="{{ route('admin.login') }}">Admin</a>
        </nav>
    </header>

    <x-hero :channel="$heroChannel ? [
        'id' => $heroChannel->id,
        'name' => $heroChannel->name,
        'logo' => $heroChannel->logo ?: asset('brand/rifi-logo.png'),
        'category' => $heroChannel->category?->name ?? $heroChannel->group_title ?? 'Featured',
        'program' => ['title' => $heroChannel->currentProgram?->title ?? 'Live broadcast'],
        'watch_url' => route('channels.show', $heroChannel),
    ] : null" />

    <section class="sat-category-rail" id="categories">
        <a href="{{ route('home') }}" class="{{ $selectedCategory === '' ? 'is-active' : '' }}">All</a>
        @foreach($categories as $category)
            <a href="{{ route('home', ['category' => $category]) }}" class="{{ $selectedCategory === $category ? 'is-active' : '' }}">
                {{ $category }}
            </a>
        @endforeach
    </section>

    @if($recommendedChannels->count())
        <section class="sat-section">
            <div class="sat-section__heading">
                <div>
                    <span>Continue watching</span>
                    <h2>Featured broadcasts</h2>
                </div>
                <a href="{{ route('live') }}">Open TV guide</a>
            </div>
            <div class="sat-feature-grid">
                @foreach($recommendedChannels as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="sat-section" id="channels">
        <div class="sat-section__heading">
            <div>
                <span>Now on air</span>
                <h2>Live channel wall</h2>
            </div>
            <p>{{ number_format($channels->total()) }} channels</p>
        </div>

        @if($channels->count() === 0)
            <div class="sat-empty">
                <strong>No channels found</strong>
                <p>Try a different search or category.</p>
            </div>
        @else
            <div class="sat-channel-grid" data-tv-grid>
                @foreach($liveChannels as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
            {{ $channels->links() }}
        @endif
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('satHomePage', () => ({
        init() {
            this.$nextTick(() => this.focusFirstCard());
            window.addEventListener('keydown', (event) => this.handleKeys(event));
        },
        focusFirstCard() {
            const first = document.querySelector('[data-tv-grid] a');
            if (first) first.setAttribute('tabindex', '0');
        },
        handleKeys(event) {
            const cards = [...document.querySelectorAll('[data-tv-grid] a')];
            const active = document.activeElement;
            const index = cards.indexOf(active);
            if (index === -1 || !['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;

            const columns = Math.max(1, Math.floor((document.querySelector('[data-tv-grid]')?.clientWidth || 1) / 260));
            const nextIndex = {
                ArrowRight: index + 1,
                ArrowLeft: index - 1,
                ArrowDown: index + columns,
                ArrowUp: index - columns,
            }[event.key];

            if (event.key === 'Enter') {
                active.click();
                return;
            }

            if (cards[nextIndex]) {
                event.preventDefault();
                cards[nextIndex].focus();
            }
        },
    }));
});
</script>
@endpush
@endsection

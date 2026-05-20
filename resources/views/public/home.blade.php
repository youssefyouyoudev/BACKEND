@extends('layouts.app')

@section('content')
<div class="rm-page rm-page--home" x-data="satHomePage()" x-init="init">
    <section class="rm-toolbar rm-glass-card" aria-label="Search live channels">
        <div>
            <span class="rm-live-badge"><i></i> Live sports hub</span>
            <strong>{{ number_format($channels->total()) }} public channels</strong>
        </div>
        <form action="{{ route('home') }}" method="GET" class="rm-search">
            @if($selectedCategory !== '')
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search live channels, football, news">
        </form>
    </section>

    <x-hero :channel="$heroChannel ? [
        'id' => $heroChannel->id,
        'name' => $heroChannel->name,
        'logo' => $heroChannel->logo ?: asset('brand/rifi-logo.png'),
        'category' => $heroChannel->category?->name ?? $heroChannel->group_title ?? 'Featured',
        'program' => ['title' => $heroChannel->currentProgram?->title ?? 'Live broadcast'],
        'watch_url' => route('channels.show', $heroChannel),
    ] : null" />

    <section class="rm-section" id="categories">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Browse by sport</p>
                <h2>Categories</h2>
            </div>
        </div>

        <div class="rm-category-chip-row" aria-label="Channel categories">
            <a href="{{ route('home') }}" class="rm-category-chip {{ $selectedCategory === '' ? 'is-active' : '' }}">All</a>
            @foreach($categories as $category)
                <a href="{{ route('home', ['category' => $category]) }}" class="rm-category-chip {{ $selectedCategory === $category ? 'is-active' : '' }}">
                    {{ $category }}
                </a>
            @endforeach
        </div>
    </section>

    @if($recommendedChannels->count())
        <section class="rm-section">
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Trending now</p>
                    <h2>Featured live broadcasts</h2>
                </div>
                <a href="{{ route('live') }}" class="rm-section-header__link">Open TV guide</a>
            </div>
            <div class="rm-match-row">
                @foreach($recommendedChannels as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </section>
    @endif

    @if($sections->count())
        <section class="rm-section" id="library">
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Curated rails</p>
                    <h2>Featured categories</h2>
                </div>
            </div>

            <div class="rm-featured-stack">
                @foreach($sections as $sectionName => $sectionChannels)
                    <div class="rm-glass-card rm-featured-rail">
                        <div class="rm-featured-rail__header">
                            <h3>{{ $sectionName ?: 'Live TV' }}</h3>
                            <span>{{ $sectionChannels->count() }} streams</span>
                        </div>
                        <div class="rm-match-row rm-match-row--compact">
                            @foreach($sectionChannels as $channel)
                                <x-channel-card :channel="$channel" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rm-section" id="channels">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Now on air</p>
                <h2>Live channel wall</h2>
            </div>
            <span class="rm-section-header__meta">{{ number_format($channels->total()) }} channels</span>
        </div>

        @if($channels->count() === 0)
            <div class="rm-empty-state">
                <span>No live channels found</span>
                <strong>Try another search or category.</strong>
                <a href="{{ route('home') }}" class="rm-btn rm-btn-secondary">Reset filters</a>
            </div>
        @else
            <div class="rm-match-grid" data-tv-grid>
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

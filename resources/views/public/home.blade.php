@extends('layouts.app')

@section('title', 'Football News, Live Scores, Fixtures & Match Updates | RifiMedia Sports')
@section('description', 'Follow football news, live scores, fixtures, standings, match previews, and sports coverage on RifiMedia Sports.')

@section('content')
<div class="rm-page rm-page--home rm-sports-home">
    <section class="rm-sports-hero">
        <div class="rm-sports-hero__content">
            <span class="rm-kicker">RifiMedia Sports</span>
            <h1>Football News, Live Scores & Match Updates</h1>
            <p>Follow fixtures, results, standings, and the latest sports stories from your favorite competitions.</p>
            <div class="rm-hero-actions">
                <a href="{{ route('scores') }}" class="rm-btn rm-btn-primary">View Live Scores</a>
                <a href="{{ route('news.index') }}" class="rm-btn rm-btn-secondary">Read Latest News</a>
            </div>
        </div>
        <div class="rm-score-ticker" aria-label="Live score ticker">
            <div class="rm-score-ticker__item">
                <span>Today</span>
                <strong>Live scores will appear when verified match data is connected.</strong>
                <a href="{{ route('scores') }}">Open scores</a>
            </div>
            <div class="rm-score-ticker__item">
                <span>Fixtures</span>
                <strong>Upcoming match cards are ready for league and team feeds.</strong>
                <a href="{{ route('fixtures') }}">View fixtures</a>
            </div>
        </div>
    </section>

    <x-ad-slot name="homepage_leaderboard" size="leaderboard" />

    <section class="rm-section rm-media-grid">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Editorial hub</p>
                <h2>Top sports coverage</h2>
            </div>
            <a href="{{ route('news.index') }}" class="rm-section-header__link">Newsroom</a>
        </div>

        <article class="rm-story-card rm-story-card--featured">
            <span class="rm-story-card__label">Match center</span>
            <h3>Previews, reports, standings, and live score context in one sports-first experience.</h3>
            <p>RifiMedia Sports is now positioned for football coverage, match updates, and responsible media presentation. Published articles will appear here once the editorial system is connected.</p>
            <a href="{{ route('fixtures') }}">Explore fixtures</a>
        </article>

        <div class="rm-story-stack">
            @foreach(['Football news desk', 'Transfer updates', 'League standings'] as $story)
                <article class="rm-story-card">
                    <span class="rm-story-card__label">Coming soon</span>
                    <h3>{{ $story }}</h3>
                    <p>Reserved editorial card with category, author, date, image, and related match metadata support.</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-section rm-layout-with-rail">
        <div>
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Fixtures</p>
                    <h2>Upcoming matches</h2>
                </div>
                <a href="{{ route('fixtures') }}" class="rm-section-header__link">Full calendar</a>
            </div>
            <div class="rm-empty-state rm-empty-state--compact">
                <span>Fixtures ready</span>
                <strong>Upcoming matches will appear here when a verified fixture feed is connected.</strong>
                <a href="{{ route('fixtures') }}" class="rm-btn rm-btn-secondary">Open fixtures</a>
            </div>
        </div>
        <aside class="rm-side-rail">
            <x-ad-slot name="homepage_sidebar_rectangle" size="rectangle" />
            <div class="rm-standings-preview">
                <span class="rm-kicker">Standings preview</span>
                <h3>League tables</h3>
                <p>Top-five standings modules are ready for reliable competition data.</p>
                <a href="{{ route('standings') }}">Open standings</a>
            </div>
        </aside>
    </section>

    <x-ad-slot name="homepage_in_feed" size="in-feed" />

    <section class="rm-section">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Trending topics</p>
                <h2>Football coverage areas</h2>
            </div>
        </div>
        <div class="rm-topic-cloud">
            @foreach(['Football', 'Transfers', 'Champions League', 'La Liga', 'Premier League', 'Botola', 'AFCON', 'Morocco National Team'] as $topic)
                <a href="{{ route('search', ['q' => $topic]) }}">{{ $topic }}</a>
            @endforeach
        </div>
    </section>

    @if($recommendedChannels->count())
        <section class="rm-section">
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Permitted media</p>
                    <h2>Featured channel information</h2>
                </div>
                <a href="{{ route('live') }}" class="rm-section-header__link">Open channels</a>
            </div>
            <div class="rm-match-row">
                @foreach($recommendedChannels as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="rm-section" id="channels">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Channel directory</p>
                <h2>Sports and media channels</h2>
                <p class="rm-section-header__meta">Categories and filters for approved public media sources.</p>
            </div>
            <span class="rm-section-header__meta">{{ number_format($channels->total()) }} public channels</span>
        </div>

        <form action="{{ route('home') }}" method="GET" class="rm-search rm-search--wide">
            @if($selectedCategory !== '')
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <input type="search" name="search" value="{{ $search }}" placeholder="Search channels, leagues, teams">
            <button class="rm-btn rm-btn-secondary rm-btn-sm" type="submit">Search</button>
        </form>

        <div class="rm-category-chip-row" aria-label="Channel categories">
            <a href="{{ route('home') }}#channels" class="rm-category-chip {{ $selectedCategory === '' ? 'is-active' : '' }}">All</a>
            @foreach($categories as $category)
                <a href="{{ route('home', ['category' => $category]) }}#channels" class="rm-category-chip {{ $selectedCategory === $category ? 'is-active' : '' }}">
                    {{ $category }}
                </a>
            @endforeach
        </div>

        @if($channels->count() === 0)
            <div class="rm-empty-state">
                <span>No channels found</span>
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
@endsection

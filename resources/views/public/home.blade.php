@extends('layouts.app')

@section('title', 'Football News, Live Scores, Fixtures & Match Updates | RifiMedia Sports')
@section('description', 'Follow football news, live scores, fixtures, standings, match previews, and sports coverage on RifiMedia Sports.')

@section('content')
<div class="rm-page rm-page--home rm-sports-home">

    {{-- ── Hero ──────────────────────────────────────────────────────── --}}
    <section class="rm-sports-hero" aria-labelledby="rm-home-hero-title">
        <div class="rm-sports-hero__content">
            <span class="rm-kicker" aria-label="Site label">⚽ RifiMedia Sports</span>
            <h1 id="rm-home-hero-title">Football News, Live Scores &amp; Match Updates</h1>
            <p>A clean home for match days: scores, fixtures, standings, team stories, and responsible sports media in one place.</p>
            <div class="rm-hero-actions">
                <a href="{{ route('scores') }}" class="rm-btn rm-btn-primary" aria-label="View live football scores">View Live Scores</a>
                <a href="{{ route('home') }}#channels" class="rm-btn rm-btn-secondary" aria-label="Browse sports channels">Browse Channels</a>
            </div>
        </div>

        {{-- Right: score ticker cards --}}
        <div class="rm-score-ticker" aria-label="Quick links">
            <div class="rm-score-ticker__item">
                <small><span class="rm-score-dot" aria-hidden="true"></span> Today</small>
                <strong>Match feed ready</strong>
                <p>Verified live scores will appear here as soon as data is connected.</p>
                <a href="{{ route('scores') }}" aria-label="Open live scores">Open scores</a>
            </div>
            <div class="rm-score-ticker__item">
                <small><span class="rm-score-dot rm-score-dot--gold" aria-hidden="true"></span> Fixtures</small>
                <strong>Calendar view prepared</strong>
                <p>Upcoming games, kickoff times, and league filters are ready.</p>
                <a href="{{ route('fixtures') }}" aria-label="View fixtures calendar">View fixtures</a>
            </div>
            <div class="rm-score-ticker__item">
                <small><span class="rm-score-dot rm-score-dot--purple" aria-hidden="true"></span> Updates</small>
                <strong>Sports hub online</strong>
                <p>News, teams, leagues, and channel discovery in one clean experience.</p>
                <a href="{{ route('news.index') }}" aria-label="Read sports news">Read news</a>
            </div>
        </div>
    </section>

    {{-- ── Leaderboard ad ────────────────────────────────────────────── --}}
    <x-ad-slot name="homepage_leaderboard" size="leaderboard" />

    {{-- ── Top sports coverage ───────────────────────────────────────── --}}
    <section class="rm-section rm-media-grid" aria-labelledby="rm-coverage-title">
        <div class="rm-section-header" style="grid-column: 1 / -1">
            <div>
                <p class="rm-eyebrow">Editorial hub</p>
                <h2 id="rm-coverage-title">Top sports coverage</h2>
            </div>
            <a href="{{ route('news.index') }}" class="rm-section-header__link">Newsroom</a>
        </div>

        {{-- Featured editorial card --}}
        <article class="rm-story-card rm-story-card--featured">
            <span class="rm-story-card__media" aria-hidden="true">MC</span>
            <span class="rm-story-card__label">Match center</span>
            <h3>Previews, reports, standings, and live score context in one calm match-day hub.</h3>
            <p>Build original coverage around the games people care about: team form, kickoff context, tactical notes, and post-match reports.</p>
            <small>Editorial-ready · No fake scores</small>
            <a href="{{ route('fixtures') }}" aria-label="Explore fixtures">Explore fixtures</a>
        </article>

        {{-- Stacked small cards --}}
        <div class="rm-story-stack">
            @foreach([
                ['icon' => 'N', 'label' => 'News desk',  'title' => 'Football news desk',  'text' => 'Publish original stories with author, date, category, image, and SEO metadata.'],
                ['icon' => 'T', 'label' => 'Trending',   'title' => 'Transfer updates',     'text' => 'Keep topic hubs friendly, scannable, and ready for verified reporting.'],
                ['icon' => 'L', 'label' => 'Tables',     'title' => 'League standings',     'text' => 'A polished route for league tables, form, fixtures, and team pages.'],
            ] as $story)
                <article class="rm-story-card">
                    <span class="rm-story-card__media" aria-hidden="true">{{ $story['icon'] }}</span>
                    <span class="rm-story-card__label">{{ $story['label'] }}</span>
                    <h3>{{ $story['title'] }}</h3>
                    <p>{{ $story['text'] }}</p>
                    <small>Coming soon</small>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ── Fixtures + sidebar ────────────────────────────────────────── --}}
    <section class="rm-section rm-layout-with-rail" aria-labelledby="rm-fixtures-title">
        <div>
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Fixtures</p>
                    <h2 id="rm-fixtures-title">Upcoming matches</h2>
                </div>
                <a href="{{ route('fixtures') }}" class="rm-section-header__link">Full calendar</a>
            </div>
            <div class="rm-fixture-preview-list" role="list">
                @foreach([
                    ['league' => 'Premier League',    'home' => 'Home Team', 'away' => 'Away Team', 'time' => 'Kickoff TBA'],
                    ['league' => 'Botola Pro',         'home' => 'Club A',    'away' => 'Club B',    'time' => 'Schedule pending'],
                    ['league' => 'Champions League',  'home' => 'Team One',  'away' => 'Team Two',  'time' => 'Fixture feed ready'],
                ] as $fixture)
                    <article class="rm-fixture-card" role="listitem">
                        <span>{{ $fixture['league'] }}</span>
                        <strong>{{ $fixture['home'] }} <em>vs</em> {{ $fixture['away'] }}</strong>
                        <small>{{ $fixture['time'] }}</small>
                        <a href="{{ route('matches.index') }}" aria-label="Open match center for {{ $fixture['home'] }} vs {{ $fixture['away'] }}">Match center</a>
                    </article>
                @endforeach
            </div>
        </div>

        <aside class="rm-side-rail" aria-label="Sidebar">
            <x-ad-slot name="homepage_sidebar_rectangle" size="rectangle" />
            <div class="rm-standings-preview">
                <span class="rm-kicker">Standings</span>
                <h3>League tables</h3>
                <div class="rm-mini-table" aria-label="Standings preview">
                    @foreach([['1', 'Team A', '68 pts'], ['2', 'Team B', '65 pts'], ['3', 'Team C', '60 pts'], ['4', 'Team D', '58 pts']] as $row)
                        <span>{{ $row[0] }}</span>
                        <strong>{{ $row[1] }}</strong>
                        <em>{{ $row[2] }}</em>
                    @endforeach
                </div>
                <p>Connect a verified standings feed to replace this preview with real league tables.</p>
                <a href="{{ route('standings') }}" aria-label="Open full standings">Open standings</a>
            </div>
        </aside>
    </section>

    {{-- ── In-feed ad ────────────────────────────────────────────────── --}}
    <x-ad-slot name="homepage_in_feed" size="in-feed" />

    {{-- ── Trending topics ───────────────────────────────────────────── --}}
    <section class="rm-section" aria-labelledby="rm-topics-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Trending topics</p>
                <h2 id="rm-topics-title">Football coverage areas</h2>
            </div>
        </div>
        <div class="rm-topic-cloud" role="list" aria-label="Trending football topics">
            @foreach(['Football', 'Transfers', 'Champions League', 'La Liga', 'Premier League', 'Botola', 'AFCON', 'Morocco National Team'] as $topic)
                <a href="{{ route('search', ['q' => $topic]) }}" role="listitem">{{ $topic }}</a>
            @endforeach
        </div>
    </section>

    {{-- ── Featured channels ─────────────────────────────────────────── --}}
    @if($recommendedChannels->count())
        <section class="rm-section" aria-labelledby="rm-featured-channels-title">
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Media guide</p>
                    <h2 id="rm-featured-channels-title">Featured sports channels</h2>
                </div>
                <a href="{{ route('live') }}" class="rm-section-header__link">All channels</a>
            </div>
            <div class="rm-match-row">
                @foreach($recommendedChannels as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Full channel directory ────────────────────────────────────── --}}
    <section class="rm-section" id="channels" aria-labelledby="rm-directory-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Channel directory</p>
                <h2 id="rm-directory-title">Browse sports media</h2>
                <p class="rm-section-header__meta">Categories and clean filters for approved public media sources.</p>
            </div>
            <span class="rm-section-header__meta" aria-live="polite">
                {{ number_format($channels->total()) }} channels
            </span>
        </div>

        {{-- Search --}}
        <form action="{{ route('home') }}" method="GET" class="rm-search rm-search--wide" role="search" aria-label="Search channels">
            @if($selectedCategory !== '')
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <input
                type="search"
                id="rm-channel-search"
                name="search"
                value="{{ $search }}"
                placeholder="Search channels, leagues, teams…"
                aria-label="Search channels"
            >
            <button class="rm-btn rm-btn-primary rm-btn-sm" type="submit" aria-label="Submit search">Search</button>
        </form>

        {{-- Category chips --}}
        <div class="rm-category-chip-row" aria-label="Filter by category" role="tablist">
            <a
                href="{{ route('home') }}#channels"
                class="rm-category-chip {{ $selectedCategory === '' ? 'is-active' : '' }}"
                role="tab"
                aria-selected="{{ $selectedCategory === '' ? 'true' : 'false' }}"
            >All</a>
            @foreach($categories as $category)
                <a
                    href="{{ route('home', ['category' => $category]) }}#channels"
                    class="rm-category-chip {{ $selectedCategory === $category ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $selectedCategory === $category ? 'true' : 'false' }}"
                >{{ $category }}</a>
            @endforeach
        </div>

        @if($channels->count() === 0)
            <div class="rm-empty-state" role="status">
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

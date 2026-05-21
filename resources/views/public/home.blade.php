@extends('layouts.app')

@section('title', 'Football News, Live Scores, Fixtures & Match Updates | RifiMedia Sports')
@section('description', 'Follow football news, live scores, fixtures, standings, match previews, and sports coverage on RifiMedia Sports.')

@section('content')
<div class="rm-page rm-page--home rm-sports-home">
    <section class="rm-sports-hero">
        <div class="rm-sports-hero__content">
            <span class="rm-kicker">RifiMedia Sports</span>
            <h1>Football News, Live Scores & Match Updates</h1>
            <p>A clean home for match days: scores, fixtures, standings, team stories, and responsible sports media in one place.</p>
            <div class="rm-hero-actions">
                <a href="{{ route('scores') }}" class="rm-btn rm-btn-primary">View Live Scores</a>
                <a href="{{ route('home') }}#channels" class="rm-btn rm-btn-secondary">Browse Channels</a>
            </div>
        </div>
        <div class="rm-score-ticker" aria-label="Live score ticker">
            <div class="rm-score-ticker__item">
                <span class="rm-score-dot"></span>
                <small>Today</small>
                <strong>Match feed ready</strong>
                <p>Verified live scores will appear here as soon as data is connected.</p>
                <a href="{{ route('scores') }}">Open scores</a>
            </div>
            <div class="rm-score-ticker__item">
                <span class="rm-score-dot rm-score-dot--gold"></span>
                <small>Fixtures</small>
                <strong>Calendar view prepared</strong>
                <p>Upcoming games, kickoff times, and league filters are ready.</p>
                <a href="{{ route('fixtures') }}">View fixtures</a>
            </div>
            <div class="rm-score-ticker__item">
                <span class="rm-score-dot rm-score-dot--purple"></span>
                <small>Updates</small>
                <strong>Sports hub online</strong>
                <p>News, teams, leagues, and channel discovery now share one clean experience.</p>
                <a href="{{ route('news.index') }}">Read news</a>
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
            <span class="rm-story-card__media" aria-hidden="true">MC</span>
            <span class="rm-story-card__label">Match center</span>
            <h3>Previews, reports, standings, and live score context in one calm match-day hub.</h3>
            <p>Build original coverage around the games people care about: team form, kickoff context, tactical notes, and post-match reports.</p>
            <small>Editorial-ready - no fake scores</small>
            <a href="{{ route('fixtures') }}">Explore fixtures</a>
        </article>

        <div class="rm-story-stack">
            @foreach([
                ['icon' => 'N', 'label' => 'News desk', 'title' => 'Football news desk', 'text' => 'Publish original stories with author, date, category, image, and SEO metadata.'],
                ['icon' => 'T', 'label' => 'Trending', 'title' => 'Transfer updates', 'text' => 'Keep topic hubs friendly, scannable, and ready for verified reporting.'],
                ['icon' => 'L', 'label' => 'Tables', 'title' => 'League standings', 'text' => 'A polished route for league tables, form, fixtures, and team pages.'],
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

    <section class="rm-section rm-layout-with-rail">
        <div>
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Fixtures</p>
                    <h2>Upcoming matches</h2>
                </div>
                <a href="{{ route('fixtures') }}" class="rm-section-header__link">Full calendar</a>
            </div>
            <div class="rm-fixture-preview-list">
                @foreach([
                    ['league' => 'Premier League', 'home' => 'Home Team', 'away' => 'Away Team', 'time' => 'Kickoff TBA'],
                    ['league' => 'Botola Pro', 'home' => 'Club A', 'away' => 'Club B', 'time' => 'Schedule pending'],
                    ['league' => 'Champions League', 'home' => 'Team One', 'away' => 'Team Two', 'time' => 'Fixture feed ready'],
                ] as $fixture)
                    <article class="rm-fixture-card">
                        <span>{{ $fixture['league'] }}</span>
                        <strong>{{ $fixture['home'] }} <em>vs</em> {{ $fixture['away'] }}</strong>
                        <small>{{ $fixture['time'] }}</small>
                        <a href="{{ route('matches.index') }}">Match center</a>
                    </article>
                @endforeach
            </div>
        </div>
        <aside class="rm-side-rail">
            <x-ad-slot name="homepage_sidebar_rectangle" size="rectangle" />
            <div class="rm-standings-preview">
                <span class="rm-kicker">Standings preview</span>
                <h3>League tables</h3>
                <div class="rm-mini-table" aria-label="Standings preview placeholder">
                    @foreach([['1', 'Team', 'Pts'], ['2', 'Team', 'Pts'], ['3', 'Team', 'Pts'], ['4', 'Team', 'Pts']] as $row)
                        <span>{{ $row[0] }}</span>
                        <strong>{{ $row[1] }}</strong>
                        <em>{{ $row[2] }}</em>
                    @endforeach
                </div>
                <p>Connect a verified standings feed to replace this preview with real league tables.</p>
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
                    <p class="rm-eyebrow">Media guide</p>
                    <h2>Featured sports channels</h2>
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
                <h2>Browse sports media</h2>
                <p class="rm-section-header__meta">Categories and clean filters for approved public media sources.</p>
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


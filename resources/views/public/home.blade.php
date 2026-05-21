@extends('layouts.app')

@section('title', 'Football Live Scores, News & Match Updates | RifiMedia Sports')
@section('description', 'Follow live scores, fixtures, league standings, transfer news and verified sports coverage in one premium football experience.')

@section('content')
@php
    $liveMatches = collect([
        ['league' => 'Premier League', 'home' => 'Arsenal', 'away' => 'Manchester City', 'score' => '2-1', 'minute' => "73'", 'time' => 'Live', 'form' => 'Title race'],
        ['league' => 'Botola Pro', 'home' => 'Raja CA', 'away' => 'Wydad AC', 'score' => '0-0', 'minute' => "28'", 'time' => 'Live', 'form' => 'Casablanca derby'],
        ['league' => 'Champions League', 'home' => 'Real Madrid', 'away' => 'Bayern Munich', 'score' => '20:00', 'minute' => 'Preview', 'time' => 'Tonight', 'form' => 'Semi-final'],
        ['league' => 'La Liga', 'home' => 'Barcelona', 'away' => 'Atletico Madrid', 'score' => '21:00', 'minute' => 'Lineups soon', 'time' => 'Today', 'form' => 'Top four'],
    ]);
    $fallbackArticles = collect([
        ['tag' => 'Transfers', 'title' => 'Summer transfer market: elite clubs line up midfield priorities', 'meta' => 'RifiMedia Sports Desk', 'url' => route('search', ['q' => 'Transfers'])],
        ['tag' => 'Morocco', 'title' => 'Morocco national team watch: form guide before the next international window', 'meta' => 'National team focus', 'url' => route('search', ['q' => 'Morocco National Team'])],
        ['tag' => 'Champions League', 'title' => 'Tactical preview: where the next European tie can be decided', 'meta' => 'Match analysis', 'url' => route('search', ['q' => 'Champions League'])],
        ['tag' => 'Premier League', 'title' => 'Weekend briefing: pressure fixtures, injury notes, and table stakes', 'meta' => 'League briefing', 'url' => route('search', ['q' => 'Premier League'])],
    ]);
    $leagues = collect([
        ['name' => 'Premier League', 'region' => 'England', 'code' => 'PL', 'slug' => 'premier-league'],
        ['name' => 'La Liga', 'region' => 'Spain', 'code' => 'LL', 'slug' => 'la-liga'],
        ['name' => 'Champions League', 'region' => 'Europe', 'code' => 'UCL', 'slug' => 'champions-league'],
        ['name' => 'Botola Pro', 'region' => 'Morocco', 'code' => 'BOT', 'slug' => 'botola-pro'],
        ['name' => 'Serie A', 'region' => 'Italy', 'code' => 'SA', 'slug' => 'serie-a'],
        ['name' => 'Bundesliga', 'region' => 'Germany', 'code' => 'BL', 'slug' => 'bundesliga'],
    ]);
    $fixtures = collect([
        ['day' => 'Today', 'league' => 'Premier League', 'match' => 'Liverpool vs Chelsea', 'time' => '18:30', 'note' => 'Lineups expected 60 minutes before kickoff'],
        ['day' => 'Today', 'league' => 'Botola Pro', 'match' => 'FAR Rabat vs RS Berkane', 'time' => '20:00', 'note' => 'Top-table pressure fixture'],
        ['day' => 'Tomorrow', 'league' => 'Champions League', 'match' => 'Inter vs PSG', 'time' => '21:00', 'note' => 'European match center'],
        ['day' => 'This week', 'league' => 'La Liga', 'match' => 'Real Betis vs Sevilla', 'time' => 'Sunday', 'note' => 'Derby watch'],
    ]);
    $topics = ['Transfers', 'AFCON', 'Morocco National Team', 'Champions League', 'Premier League', 'La Liga'];
    $channelTabs = ['Sports', 'Arabic', 'International', 'Kids', 'Movies'];
@endphp

<div class="rm-page rm-page--home rm-sports-home rm-premium-home">
    <section class="rm-premium-hero" aria-labelledby="rm-home-hero-title">
        <div class="rm-premium-hero__content">
            <span class="rm-kicker">RifiMedia Sports</span>
            <h1 id="rm-home-hero-title">Football Live Scores, News &amp; Match Updates</h1>
            <p>Follow live scores, fixtures, league standings, transfer news and verified sports coverage in one premium football experience.</p>
            <div class="rm-hero-actions">
                <a href="{{ route('scores') }}" class="rm-btn rm-btn-primary">Live Scores</a>
                <a href="{{ route('live') }}" class="rm-btn rm-btn-secondary">Watch Channels</a>
            </div>
        </div>

        <div class="rm-dashboard-mockup" aria-label="Football dashboard preview">
            <div class="rm-dashboard-mockup__header">
                <span>Match Center</span>
                <strong>Live</strong>
            </div>
            <div class="rm-live-score-panel">
                <span>Premier League</span>
                <div><strong>ARS</strong><b>2 - 1</b><strong>MCI</strong></div>
                <small>73' - Arsenal pressure, 58% possession</small>
            </div>
            <div class="rm-dashboard-grid">
                <article>
                    <span>Top Table</span>
                    <p><b>1</b> Arsenal <em>74 pts</em></p>
                    <p><b>2</b> Man City <em>72 pts</em></p>
                    <p><b>3</b> Liverpool <em>70 pts</em></p>
                </article>
                <article>
                    <span>Next Up</span>
                    <p><b>20:00</b> Real Madrid vs Bayern</p>
                    <p><b>21:00</b> Barcelona vs Atletico</p>
                </article>
            </div>
            <div class="rm-stat-strip">
                <span><b>18</b> Live games</span>
                <span><b>42</b> Fixtures</span>
                <span><b>120K</b> Viewers</span>
            </div>
        </div>
    </section>

    <section class="rm-section rm-live-matches-section" aria-labelledby="rm-live-matches-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Live now</p>
                <h2 id="rm-live-matches-title">Featured live matches</h2>
            </div>
            <a href="{{ route('scores') }}" class="rm-section-header__link">All scores</a>
        </div>
        <div class="rm-live-match-slider" role="list">
            @foreach($liveMatches as $match)
                <article class="rm-live-match-card" role="listitem">
                    <div class="rm-live-match-card__top">
                        <span>{{ $match['league'] }}</span>
                        <b>{{ $match['time'] }}</b>
                    </div>
                    <div class="rm-live-match-card__teams">
                        <span class="rm-team-crest">{{ Str::substr($match['home'], 0, 2) }}</span>
                        <strong>{{ $match['home'] }}</strong>
                        <em>{{ $match['score'] }}</em>
                        <strong>{{ $match['away'] }}</strong>
                        <span class="rm-team-crest">{{ Str::substr($match['away'], 0, 2) }}</span>
                    </div>
                    <small><i aria-hidden="true"></i>{{ $match['minute'] }} - {{ $match['form'] }}</small>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-section rm-editorial-grid" aria-labelledby="rm-news-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Trending football news</p>
                <h2 id="rm-news-title">Stories shaping the game</h2>
            </div>
            <a href="{{ route('news.index') }}" class="rm-section-header__link">Newsroom</a>
        </div>
        <article class="rm-editorial-card rm-editorial-card--lead">
            <span>{{ $fallbackArticles[0]['tag'] }}</span>
            <h3>{{ $fallbackArticles[0]['title'] }}</h3>
            <p>Daily football context with a sharper signal: transfer priorities, credible reporting, tactical notes, and league implications.</p>
            <a href="{{ $fallbackArticles[0]['url'] }}">Read briefing</a>
        </article>
        <div class="rm-editorial-stack">
            @foreach($fallbackArticles->slice(1) as $article)
                <article class="rm-editorial-card">
                    <span>{{ $article['tag'] }}</span>
                    <h3>{{ $article['title'] }}</h3>
                    <small>{{ $article['meta'] }}</small>
                    <a href="{{ $article['url'] }}">Explore</a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-section" aria-labelledby="rm-leagues-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Leagues</p>
                <h2 id="rm-leagues-title">Follow top competitions</h2>
            </div>
            <a href="{{ route('leagues.index') }}" class="rm-section-header__link">League directory</a>
        </div>
        <div class="rm-league-grid">
            @foreach($leagues as $league)
                <article class="rm-league-card">
                    <span>{{ $league['code'] }}</span>
                    <h3>{{ $league['name'] }}</h3>
                    <p>{{ $league['region'] }}</p>
                    <div>
                        <a href="{{ route('standings') }}">Standings</a>
                        <a href="{{ route('fixtures') }}">Fixtures</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-section" id="channels" aria-labelledby="rm-channels-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Channels</p>
                <h2 id="rm-channels-title">Watch premium sports media</h2>
            </div>
            <a href="{{ route('live') }}" class="rm-section-header__link">All channels</a>
        </div>
        <div class="rm-channel-tabs" role="tablist" aria-label="Channel filters">
            @foreach($channelTabs as $index => $tab)
                <a href="{{ $index === 0 ? route('home').'#channels' : route('home', ['category' => $tab]).'#channels' }}" class="{{ ($index === 0 && $selectedCategory === '') || $selectedCategory === $tab ? 'is-active' : '' }}" role="tab">{{ $tab }}</a>
            @endforeach
        </div>

        @if($recommendedChannels->count())
            <div class="rm-match-grid rm-premium-channel-grid">
                @foreach($recommendedChannels->take(8) as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        @else
            <div class="rm-skeleton-grid" aria-label="Loading channel recommendations">
                @for($i = 0; $i < 4; $i++)
                    <span class="rm-skeleton-card"></span>
                @endfor
            </div>
        @endif
    </section>

    <section class="rm-section rm-match-center" aria-labelledby="rm-match-center-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Match center</p>
                <h2 id="rm-match-center-title">Fixtures to explore</h2>
            </div>
            <a href="{{ route('fixtures') }}" class="rm-section-header__link">Full calendar</a>
        </div>
        <div class="rm-fixture-tabs" role="tablist" aria-label="Fixture ranges">
            <button class="is-active" type="button">Today</button>
            <button type="button">Tomorrow</button>
            <button type="button">This week</button>
        </div>
        <div class="rm-fixture-board">
            @foreach($fixtures as $fixture)
                <article class="rm-fixture-row">
                    <span>{{ $fixture['day'] }}</span>
                    <strong>{{ $fixture['match'] }}</strong>
                    <em>{{ $fixture['league'] }}</em>
                    <b>{{ $fixture['time'] }}</b>
                    <small>{{ $fixture['note'] }}</small>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-section" aria-labelledby="rm-topics-title">
        <div class="rm-section-header">
            <div>
                <p class="rm-eyebrow">Football topics</p>
                <h2 id="rm-topics-title">Trending categories</h2>
            </div>
        </div>
        <div class="rm-topic-cloud" role="list">
            @foreach($topics as $topic)
                <a href="{{ route('search', ['q' => $topic]) }}" role="listitem">{{ $topic }}</a>
            @endforeach
        </div>
    </section>
</div>
@endsection

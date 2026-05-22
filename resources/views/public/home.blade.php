@extends('layouts.app')

@section('title', 'RifiMedia | Live TV, Football Scores & Entertainment')
@section('description', 'Live TV, football scores, and entertainment in one simple place. Watch real channels, follow football, and discover upcoming media sections.')

@section('content')
<div class="rm-page rm-media-platform-page">
    <section class="rm-platform-hero" aria-labelledby="rm-home-hero-title">
        <div>
            <span class="rm-kicker">RifiMedia</span>
            <h1 id="rm-home-hero-title">RifiMedia</h1>
            <p>Live TV, football scores, and entertainment in one simple place.</p>
            <div class="rm-hero-actions">
                <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-primary">Watch Live TV</a>
                <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-secondary">View Football Scores</a>
            </div>
        </div>
        <div class="rm-platform-hero__panel" aria-label="RifiMedia sections">
            <a href="{{ route('live-tv') }}"><strong>Live TV</strong><span>{{ number_format($recommendedChannels->count()) }} featured channels</span></a>
            <a href="{{ route('sports.football') }}"><strong>Football</strong><span>Scores, fixtures, TV channels</span></a>
            <a href="{{ route('movies') }}"><strong>Movies</strong><span>Coming soon</span></a>
            <a href="{{ route('tv-shows') }}"><strong>TV Shows</strong><span>Coming soon</span></a>
            <a href="{{ route('anime') }}"><strong>Anime</strong><span>Coming soon</span></a>
        </div>
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Live now" title="Live TV channels" href="{{ route('live-tv') }}" action="Open Live TV" />
        @if($recommendedChannels->count())
            <div class="rm-match-grid rm-premium-channel-grid">
                @foreach($recommendedChannels->take(8) as $channel)
                    <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        @else
            <x-empty-state title="No live channels yet" message="Import and approve a public playlist to show live channels here." action="Live TV" :href="route('live-tv')" />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Browse" title="Categories" href="{{ route('live-tv') }}" action="All categories" />
        @if($categories->count())
            <div class="rm-category-strip">
                @foreach($categories->take(10) as $category)
                    <a href="{{ route('live-tv', ['category' => $category]) }}">{{ $category }}</a>
                @endforeach
            </div>
        @else
            <x-empty-state title="No categories yet" message="Channel categories will appear after approved playlist channels are imported." />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Football" title="Today's matches" href="{{ route('sports.football') }}" action="All scores" />
        @if($footballMatches->count())
            <div class="football-match-grid">
                @foreach($footballMatches as $match)
                    <x-match-card :match="$match" />
                @endforeach
            </div>
        @else
            <x-empty-state title="No matches available" message="Football matches will appear when TheSportsDB has fixtures for your configured leagues." action="Football Scores" :href="route('sports.football')" />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Entertainment" title="More sections are coming" />
        <div class="rm-media-grid">
            <x-media-card title="Movies" description="A clean movie experience is being prepared." :href="route('movies')" label="Coming soon" disabled />
            <x-media-card title="TV Shows" description="Schedules, show pages, and watchlists are coming." :href="route('tv-shows')" label="Coming soon" disabled />
            <x-media-card title="Anime" description="Anime episodes and release guides are coming." :href="route('anime')" label="Coming soon" disabled />
        </div>
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="News" title="Latest stories" href="{{ route('news.index') }}" action="Newsroom" />
        @if($articles->count())
            <div class="rm-media-grid">
                @foreach($articles as $article)
                    <x-media-card
                        :title="$article->title"
                        :description="$article->excerpt"
                        :href="route('news.show', $article->slug)"
                        :image="$article->featured_image"
                        :label="$article->category?->name ?: 'News'"
                    />
                @endforeach
            </div>
        @else
            <x-empty-state title="No news published" message="Published articles will appear here when the newsroom has real stories." action="Browse News" :href="route('news.index')" />
        @endif
    </section>
</div>
@endsection

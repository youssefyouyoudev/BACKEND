@extends('layouts.app')

@section('title', 'RifiMedia | Live TV, Football Scores & Sports Streaming')
@section('description', 'Watch live TV channels, follow football scores, discover sports news, and browse premium entertainment coverage on RifiMedia.')

@section('content')
@php
    $homeCategories = $categories
        ->reject(fn ($category) => in_array(strtolower((string) $category), ['movies', 'movie', 'tv shows', 'tv-shows', 'anime'], true))
        ->take(8);
@endphp

<div class="rm-page rm-media-platform-page">
    <section class="rm-platform-hero rm-cinematic-hero" aria-labelledby="rm-home-hero-title" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <div class="rm-hero-copy" data-reveal>
            <span class="rm-kicker"><x-icon name="signal" /> RifiMedia Live</span>
            <h1 id="rm-home-hero-title">Football scores, live TV, and sports stories in one premium hub.</h1>
            <p>RifiMedia brings live football, channels, scores, and match updates into one clean premium platform.</p>
            <div class="rm-hero-actions">
                <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-primary"><x-icon name="play" />Watch Live</a>
                <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-secondary"><x-icon name="scores" />View Scores</a>
            </div>
            <div class="rm-trust-strip" aria-label="RifiMedia highlights">
                <span><x-icon name="tv" /><strong>{{ number_format($recommendedChannels->count()) }}</strong> live channels</span>
                <span><x-icon name="trophy" /><strong>{{ count(config('football_leagues.top_leagues', [])) }}</strong> competitions</span>
                <span><x-icon name="signal" /><strong>Live</strong> match updates</span>
            </div>
        </div>

        <div class="rm-media-wall" aria-hidden="true">
            <div class="rm-media-wall__rail rm-media-wall__rail--left"></div>
            <div class="rm-media-wall__rail rm-media-wall__rail--right"></div>
            <div class="rm-media-wall__screen">
                <span class="rm-live-badge"><i></i> Now playing</span>
                <img src="{{ config('rifimedia_visuals.images.stadium_night') }}" alt="" loading="eager">
                <strong>RifiMedia Live</strong>
                <small>Channels, fixtures, and match updates</small>
            </div>
            <div class="rm-media-wall__card rm-media-wall__card--match">
                <x-icon name="football" />
                <span>Today</span>
                <strong>Fixtures & scores</strong>
            </div>
            <div class="rm-media-wall__card rm-media-wall__card--channel">
                <x-icon name="tv" />
                <span>Channels</span>
                <strong>Browse by category</strong>
            </div>
            <div class="rm-media-wall__card rm-media-wall__card--news">
                <x-icon name="trending" />
                <span>Updates</span>
                <strong>Latest sports stories</strong>
            </div>
        </div>
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Live now" title="Featured live channels" description="Fast access to approved public channels with a premium player experience." href="{{ route('live-tv') }}" action="Open Live TV" />
        @if($recommendedChannels->count())
            <div class="rm-carousel-shell">
                <button type="button" class="rm-carousel-arrow rm-carousel-arrow--prev" data-carousel-prev aria-label="Scroll featured channels left"><x-icon name="chevron-right" /></button>
                <div class="rm-match-row rm-premium-channel-grid" data-carousel>
                    @foreach($recommendedChannels->take(8) as $channel)
                        <x-channel-card :channel="$channel" />
                    @endforeach
                </div>
                <button type="button" class="rm-carousel-arrow rm-carousel-arrow--next" data-carousel-next aria-label="Scroll featured channels right"><x-icon name="chevron-right" /></button>
            </div>
        @else
            <x-empty-state title="No live channels right now" message="Approved channels will appear here as soon as they are available." action="Open Live TV" :href="route('live-tv')" />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Today's football" title="Live scores and fixtures" description="Follow today's fixtures and find where to watch when channels are available." href="{{ route('sports.football') }}" action="All scores" />
        @if($footballMatches->count())
            <div class="football-match-grid">
                @foreach($footballMatches as $match)
                    <x-match-card :match="$match" />
                @endforeach
            </div>
        @else
            <x-empty-state title="No live matches right now" message="Check upcoming fixtures or try another date." action="Football Scores" :href="route('sports.football')" />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Channels" title="Categories" description="Browse live channels by category and jump straight into the player." href="{{ route('live-tv') }}" action="All categories" />
        @if($homeCategories->count())
            <div class="rm-home-category-grid">
                @foreach($homeCategories as $category)
                    <a href="{{ route('live-tv', ['category' => $category]) }}" data-reveal>
                        <span><x-icon name="tv" /></span>
                        <strong>{{ $category }}</strong>
                        <small>Browse live channels</small>
                    </a>
                @endforeach
            </div>
        @else
            <x-empty-state title="No channel categories available" message="Browse Live TV while categories are being organized." action="Open Live TV" :href="route('live-tv')" />
        @endif
    </section>

    @if($articles->count())
        <section class="rm-section">
            <x-section-header eyebrow="Latest sports updates" title="Football news" href="{{ route('news.index') }}" action="Newsroom" />
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
        </section>
    @endif

    <section class="rm-section rm-seo-panel">
        <x-section-header eyebrow="Why RifiMedia" title="A cleaner way to follow live sports and TV" />
        <div class="rm-feature-grid">
            <article data-reveal><x-icon name="scores" /><strong>Live scores</strong><p>Follow today's fixtures, results, kickoff times, and match status without clutter.</p></article>
            <article data-reveal><x-icon name="play" /><strong>Watch links</strong><p>Find available TV channels and jump straight into the player when a stream exists.</p></article>
            <article data-reveal><x-icon name="tv" /><strong>Channel discovery</strong><p>Browse live channels by category with search, selected states, and fast switching.</p></article>
        </div>
    </section>

    <section class="rm-section rm-home-copy-block">
        <div>
            <p class="rm-eyebrow">RifiMedia</p>
            <h2>Live football, channels, scores, and match updates in one place.</h2>
        </div>
        <p>Open live channels, check today's football, browse useful categories, and read sports updates when the newsroom has published coverage. The homepage stays focused on what is available now, without fake previews or unfinished sections.</p>
    </section>
</div>
@endsection

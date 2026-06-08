@extends('layouts.app')

@section('title', 'RiFi Media TV - World Cup 2026 Live TV Experience')
@section('description', 'Watch live TV channels and enjoy a smooth World Cup 2026 inspired broadcast experience on RiFi Media TV.')
@section('image', asset('assets/images/fifa_world_cup_2026_tease.png'))

@section('content')
@php
    $homeCategories = $categories
        ->reject(fn ($category) => in_array(strtolower((string) $category), ['movies', 'movie', 'tv shows', 'tv-shows', 'anime'], true))
        ->take(8);

    $readyFeatures = [
        ['icon' => 'signal', 'title' => 'Fast streaming', 'arabic' => 'بث سريع', 'copy' => 'Protected playback links and automatic recovery keep the experience moving.'],
        ['icon' => 'search', 'title' => 'Clean browsing', 'arabic' => 'تصفح سهل', 'copy' => 'Find channels quickly with search, categories, and grouped quality options.'],
        ['icon' => 'tv', 'title' => 'Mobile friendly', 'arabic' => 'متوافق مع الهاتف', 'copy' => 'A responsive receiver designed for phones, tablets, laptops, and large screens.'],
        ['icon' => 'play', 'title' => 'Auto reconnect', 'arabic' => 'إعادة اتصال تلقائية', 'copy' => 'The player watches for interruptions and reconnects without refreshing the page.'],
    ];
@endphp

<div class="wc-page">
    @include('partials.worldcup-hero', ['liveChannelCount' => $liveChannelCount])

    <section class="wc-section" aria-labelledby="wc-ready-title">
        <div class="wc-section__heading">
            <div>
                <span class="wc-badge">Tournament ready · جاهزون</span>
                <h2 id="wc-ready-title" class="wc-title">World Cup Ready</h2>
                <p class="wc-subtitle">Everything you need for a simple, fast football broadcast experience.</p>
            </div>
            <p class="wc-section__arabic" dir="rtl">جاهزون لكأس العالم</p>
        </div>
        <div class="wc-feature-grid">
            @foreach($readyFeatures as $feature)
                <article class="wc-card wc-feature-card" data-reveal>
                    <span class="wc-feature-card__icon"><x-icon :name="$feature['icon']" /></span>
                    <strong>{{ $feature['title'] }}</strong>
                    <b dir="rtl">{{ $feature['arabic'] }}</b>
                    <p>{{ $feature['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="wc-section wc-experience" id="channels" aria-labelledby="wc-experience-title">
        <div class="wc-section__heading">
            <div>
                <span class="wc-badge wc-live-badge"><i></i> Live TV</span>
                <h2 id="wc-experience-title" class="wc-title">Live TV Experience</h2>
                <p class="wc-subtitle">A cinematic player, clean search, and one-tap channel switching.</p>
            </div>
            <a href="{{ route('live-tv') }}" class="wc-button wc-button--primary">Open Live TV <x-icon name="arrow-up-right" /></a>
        </div>

        <div class="wc-experience__layout">
            <a href="{{ route('live-tv') }}" class="wc-player-shell wc-experience__player">
                <div class="wc-experience__screen">
                    <img src="{{ asset('assets/images/fifa_world_cup_2026_tease.png') }}" alt="" loading="lazy">
                    <span class="wc-live-badge"><i></i> Live receiver</span>
                    <div>
                        <x-icon name="play" />
                        <strong>Start watching</strong>
                        <small>اختر القناة وابدأ المشاهدة</small>
                    </div>
                </div>
                <footer>
                    <span><x-icon name="signal" /> Automatic reconnect</span>
                    <span><x-icon name="search" /> Fast channel search</span>
                </footer>
            </a>

            <div class="wc-experience__channels wc-glass">
                <header>
                    <div><span class="wc-live-badge"><i></i> On air</span><strong>Featured channels</strong></div>
                    <a href="{{ route('live-tv') }}">View all</a>
                </header>
                @if($recommendedChannels->count())
                    <div class="wc-channel-preview">
                        @foreach($recommendedChannels->take(4) as $channel)
                            <x-channel-card :channel="$channel" />
                        @endforeach
                    </div>
                @else
                    <x-empty-state title="Coming soon" message="Public live channels will appear here when available." action="Open Live TV" :href="route('live-tv')" />
                @endif
            </div>
        </div>
    </section>

    <section class="wc-section wc-upcoming" aria-labelledby="wc-upcoming-title">
        <div class="wc-upcoming__poster wc-card" data-reveal>
            <img
                src="{{ asset('assets/images/fifa_world_cup_2026_tease.png') }}"
                alt="World Cup 2026 upcoming broadcast on RiFi Media TV"
                loading="lazy"
                width="1122"
                height="1402"
            >
        </div>
        <div class="wc-upcoming__copy" data-reveal>
            <span class="wc-badge">Next broadcast · البث القادم</span>
            <h2 id="wc-upcoming-title" class="wc-title">Upcoming Broadcast</h2>
            <p class="wc-subtitle">The World Cup 2026 live experience begins on RiFi Media TV on June 11.</p>
            <h3 dir="rtl">البث القادم</h3>
            <p dir="rtl">تنطلق تجربة كأس العالم 2026 مباشرة على RiFi Media TV يوم 11 يونيو.</p>
            @include('partials.worldcup-countdown')
            <a href="{{ route('live-tv') }}" class="wc-button wc-button--primary"><x-icon name="play" /> Watch Live / شاهد الآن</a>
        </div>
    </section>

    <section class="wc-section" aria-labelledby="wc-how-title">
        <div class="wc-section__heading">
            <div>
                <span class="wc-badge">Three simple steps</span>
                <h2 id="wc-how-title" class="wc-title">How It Works</h2>
            </div>
            <p class="wc-section__arabic" dir="rtl">كيف يعمل</p>
        </div>
        <div class="wc-steps">
            <article class="wc-card"><b>01</b><x-icon name="search" /><strong>Choose a channel</strong><span dir="rtl">اختر قناة</span></article>
            <article class="wc-card"><b>02</b><x-icon name="play" /><strong>Start watching</strong><span dir="rtl">ابدأ المشاهدة</span></article>
            <article class="wc-card"><b>03</b><x-icon name="football" /><strong>Enjoy the match</strong><span dir="rtl">استمتع بالمباراة</span></article>
        </div>
    </section>

    <section class="wc-section">
        <x-section-header eyebrow="Matchday" title="Live scores and fixtures" description="Follow today's football and open match details without leaving RiFi Media TV." href="{{ route('sports.football') }}" action="All scores" />
        @if($footballMatches->count())
            <div class="football-match-grid">
                @foreach($footballMatches as $match)
                    <x-match-card :match="$match" class="wc-card" />
                @endforeach
            </div>
        @else
            <x-empty-state title="No live matches right now" message="Upcoming fixtures will appear here as matchday approaches." action="Football Scores" :href="route('sports.football')" class="wc-empty-state" />
        @endif
    </section>

    <section class="wc-section">
        <x-section-header eyebrow="Channel zones" title="Explore categories" description="Jump straight into the type of channel you want." href="{{ route('live-tv') }}" action="All channels" />
        @if($homeCategories->count())
            <div class="wc-category-grid">
                @foreach($homeCategories as $category)
                    <a href="{{ route('live-tv', ['category' => $category]) }}" class="wc-card wc-category-card" data-reveal>
                        <span><x-icon name="tv" /></span>
                        <strong>{{ $category }}</strong>
                        <small>Browse live channels <x-icon name="arrow-up-right" /></small>
                    </a>
                @endforeach
            </div>
        @else
            <x-empty-state title="Channels are being prepared" message="Open Live TV to see every currently available channel." action="Open Live TV" :href="route('live-tv')" class="wc-empty-state" />
        @endif
    </section>

    @if($articles->count())
        <section class="wc-section">
            <x-section-header eyebrow="Tournament newsroom" title="Latest football stories" href="{{ route('news.index') }}" action="Newsroom" />
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
</div>
@endsection

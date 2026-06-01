@extends('layouts.app')

@section('title', 'Football Live Scores & TV Channels | RifiMedia')
@section('description', 'Today football matches, recent results, upcoming fixtures, and TV channels with direct watch links from RifiMedia playlists.')

@section('content')
<div
    class="rm-page football-live-page"
    data-football-live
    data-today-url="{{ route('api.football.today') }}"
    data-date-url="{{ route('api.football.date') }}"
    data-upcoming-url="{{ route('api.football.upcoming') }}"
    data-results-url="{{ route('api.football.results') }}"
    data-event-url-template="{{ url('/api/football/event/__EVENT_ID__') }}"
    data-tv-url-template="{{ url('/api/football/event/__EVENT_ID__/tv') }}"
>
    <nav class="football-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span aria-hidden="true">/</span>
        <span>Football</span>
    </nav>

    <section class="football-live-hero" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <div data-reveal>
            <span class="rm-kicker"><x-icon name="football" /> RifiMedia</span>
            <h1>Football live scores, fixtures, and TV channels</h1>
            <p>Track live matches, kickoff times, results, and broadcast availability from a match-day dashboard built for quick scanning.</p>
            <div class="rm-hero-microstats" aria-label="Football page highlights">
                <span><x-icon name="calendar" /> Date filters</span>
                <span><x-icon name="trophy" /> League grouping</span>
                <span><x-icon name="tv" /> Watch indicators</span>
            </div>
        </div>
        <button type="button" class="football-refresh-btn" data-football-refresh>
            <x-icon name="signal" />
            Refresh
        </button>
    </section>

    <section class="football-filter-panel" aria-label="Football filters">
        <div class="football-quick-filters" role="tablist" aria-label="Match range">
            <button type="button" data-football-filter="today" class="is-active"><x-icon name="calendar" />Today</button>
            <button type="button" data-football-filter="live"><x-icon name="signal" />Live</button>
            <button type="button" data-football-filter="tomorrow"><x-icon name="calendar" />Tomorrow</button>
            <button type="button" data-football-filter="yesterday"><x-icon name="clock" />Yesterday</button>
            <button type="button" data-football-filter="upcoming"><x-icon name="trending" />Upcoming</button>
            <button type="button" data-football-filter="results"><x-icon name="scores" />Results</button>
        </div>

        <div class="football-date-filter">
            <label for="football-date">Jump to date</label>
            <input id="football-date" type="date" data-football-date value="{{ now()->toDateString() }}">
        </div>

        <label class="football-search-filter" for="football-search">
            <span>Search matches</span>
            <input id="football-search" type="search" data-football-search placeholder="Search team or league">
        </label>
    </section>

    <section class="football-league-strip" aria-label="Configured top leagues">
        <button type="button" data-football-league="All" class="is-active">
            <strong>All leagues</strong>
            <small>Every match</small>
        </button>
        @foreach($leagues as $league)
            <button type="button" data-football-league="{{ $league['name'] }}">
                <strong>{{ $league['name'] }}</strong>
                <small>{{ $league['country'] }}</small>
            </button>
        @endforeach
    </section>

    <section class="football-country-filter" aria-label="TV country filter">
        <span>TV region</span>
        @foreach(['All', 'Morocco', 'MENA', 'United Kingdom', 'France', 'Spain', 'Germany', 'Italy'] as $country)
            <button type="button" data-tv-country="{{ $country }}" class="{{ $loop->first ? 'is-active' : '' }}">{{ $country }}</button>
        @endforeach
    </section>

    <section class="football-match-shell" aria-live="polite" aria-busy="false">
        <div class="football-match-shell__header">
            <div>
                <p class="rm-eyebrow">Match feed</p>
                <h2>Scores and fixtures</h2>
            </div>
            <span data-football-count>Loading matches...</span>
        </div>
        <div data-football-status class="football-status sr-only"></div>
        <div data-football-matches class="football-match-grid">
            @for($i = 0; $i < 4; $i++)
                <article class="football-match-card football-match-card--skeleton">
                    <span></span>
                    <div></div>
                    <strong></strong>
                    <p></p>
                </article>
            @endfor
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/football-live.js')
@endpush

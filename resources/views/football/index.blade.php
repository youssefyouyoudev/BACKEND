@extends('layouts.app')

@section('title', 'Football Live Scores & TV Channels | RifiMedia Sports')
@section('description', 'Today football matches, recent results, upcoming fixtures, and TV channels with direct watch links from RifiMedia Sports playlists.')

@section('content')
<div
    class="rm-page football-live-page"
    data-football-live
    data-today-url="{{ route('football.api.today') }}"
    data-date-url="{{ route('football.api.date') }}"
    data-upcoming-url="{{ route('football.api.upcoming') }}"
    data-results-url="{{ route('football.api.results') }}"
    data-event-url-template="{{ url('/football/api/event/__EVENT_ID__') }}"
    data-tv-url-template="{{ url('/football/api/event/__EVENT_ID__/tv') }}"
>
    <nav class="football-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span aria-hidden="true">/</span>
        <span>Football</span>
    </nav>

    <section class="football-live-hero">
        <div>
            <span class="rm-kicker">Live football center</span>
            <h1>Football Live Scores &amp; TV Channels</h1>
            <p>Follow top European football fixtures, results, live status, and broadcast availability with direct links to channels already available in your RifiMedia playlist.</p>
        </div>
        <button type="button" class="football-refresh-btn" data-football-refresh>
            <span aria-hidden="true">↻</span>
            Refresh
        </button>
    </section>

    <section class="football-filter-panel" aria-label="Football filters">
        <div class="football-quick-filters" role="tablist" aria-label="Match range">
            <button type="button" data-football-filter="today" class="is-active">Today</button>
            <button type="button" data-football-filter="tomorrow">Tomorrow</button>
            <button type="button" data-football-filter="yesterday">Yesterday</button>
            <button type="button" data-football-filter="upcoming">Upcoming</button>
            <button type="button" data-football-filter="results">Results</button>
        </div>

        <div class="football-date-filter">
            <label for="football-date">Jump to date</label>
            <input id="football-date" type="date" data-football-date value="{{ now()->toDateString() }}">
        </div>
    </section>

    <section class="football-league-strip" aria-label="Configured top leagues">
        @foreach($leagues as $league)
            <span class="{{ empty($league['id']) ? 'is-missing' : '' }}">
                <strong>{{ $league['name'] }}</strong>
                <small>{{ $league['country'] }}{{ empty($league['id']) ? ' · ID needed' : '' }}</small>
            </span>
        @endforeach
    </section>

    <section class="football-country-filter" aria-label="TV country filter">
        <span>TV region</span>
        @foreach(['All', 'Morocco', 'MENA', 'United Kingdom', 'France', 'Spain', 'Germany', 'Italy'] as $country)
            <button type="button" data-tv-country="{{ $country }}" class="{{ $loop->first ? 'is-active' : '' }}">{{ $country }}</button>
        @endforeach
    </section>

    <section class="football-match-shell" aria-live="polite" aria-busy="false">
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

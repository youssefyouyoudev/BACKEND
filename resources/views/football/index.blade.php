@extends('layouts.app')

@section('title', __("Football Live Scores & TV Channels | RifiMedia"))
@section('description', __("Today football matches, recent results, upcoming fixtures, and TV channels with direct watch links from RifiMedia playlists."))

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
    <nav class="football-breadcrumb" aria-label="{{ __("Breadcrumb") }}">
        <a href="{{ route('home') }}">{{ __("Home") }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ __("Football") }}</span>
    </nav>

    <section class="football-live-hero" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <div data-reveal>
            <span class="rm-kicker"><x-icon name="football" /> {{ __("RifiMedia") }}</span>
            <h1>{{ __("Football live scores, fixtures, and TV channels") }}</h1>
            <p>{{ __("Track live matches, kickoff times, results, and broadcast availability from a match-day dashboard built for quick scanning.") }}</p>
            <div class="rm-hero-microstats" aria-label="{{ __("Football page highlights") }}">
                <span><x-icon name="calendar" /> {{ __("Date filters") }}</span>
                <span><x-icon name="trophy" /> {{ __("League grouping") }}</span>
                <span><x-icon name="tv" /> {{ __("Watch indicators") }}</span>
            </div>
        </div>
        <button type="button" class="football-refresh-btn rm-btn rm-btn-secondary" data-football-refresh>
            <x-icon name="signal" />
            {{ __("Refresh") }}
        </button>
    </section>

    <section class="football-filter-panel" aria-label="{{ __("Football filters") }}">
        <div class="football-quick-filters chip-scroll" role="tablist" aria-label="{{ __("Match range") }}">
            <button type="button" data-football-filter="today" class="chip is-active chip-active"><x-icon name="calendar" />{{ __("Today") }}</button>
            <button type="button" data-football-filter="live" class="chip"><x-icon name="signal" />{{ __('live.label') }}</button>
            <button type="button" data-football-filter="tomorrow" class="chip"><x-icon name="calendar" />{{ __("Tomorrow") }}</button>
            <button type="button" data-football-filter="yesterday" class="chip"><x-icon name="clock" />{{ __("Yesterday") }}</button>
            <button type="button" data-football-filter="upcoming" class="chip"><x-icon name="trending" />{{ __("Upcoming") }}</button>
            <button type="button" data-football-filter="results" class="chip"><x-icon name="scores" />{{ __("Results") }}</button>
        </div>

        <div class="football-date-filter">
            <label for="football-date">{{ __("Jump to date") }}</label>
            <input id="football-date" type="date" data-football-date value="{{ now()->toDateString() }}">
        </div>

        <label class="football-search-filter" for="football-search">
            <span>{{ __("Search matches") }}</span>
            <input id="football-search" type="search" data-football-search placeholder="{{ __("Search team or league") }}">
        </label>
    </section>

    <section class="football-league-strip" aria-label="{{ __("Configured top leagues") }}">
        <button type="button" data-football-league="All" class="chip is-active chip-active">
            <strong>{{ __("All leagues") }}</strong>
            <small>{{ __("Every match") }}</small>
        </button>
        @foreach($leagues as $league)
            <button type="button" data-football-league="{{ $league['name'] }}" class="chip">
                <strong>{{ $league['name'] }}</strong>
                <small>{{ $league['country'] }}</small>
            </button>
        @endforeach
    </section>

    <section class="football-country-filter" aria-label="{{ __("TV country filter") }}">
        <span>{{ __("TV region") }}</span>
        @foreach(['All', 'Morocco', 'MENA', 'United Kingdom', 'France', 'Spain', 'Germany', 'Italy'] as $country)
            <button type="button" data-tv-country="{{ $country }}" class="chip {{ $loop->first ? 'is-active chip-active' : '' }}">{{ $country }}</button>
        @endforeach
    </section>

    <section class="football-match-shell" aria-live="polite" aria-busy="false">
        <div class="football-match-shell__header">
            <div>
                <p class="rm-eyebrow">{{ __("Match feed") }}</p>
                <h2>{{ __("Scores and fixtures") }}</h2>
            </div>
            <span data-football-count>{{ __("Loading matches...") }}</span>
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

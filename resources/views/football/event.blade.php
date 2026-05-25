@extends('layouts.app')

@section('title', ($match['home_team']['name'] ?? 'Match').' vs '.($match['away_team']['name'] ?? 'Match').' | Football Match Center')
@section('description', 'Football match details, status, score, venue, and TV channel availability with direct watch links.')

@section('content')
<div class="rm-page football-live-page" data-football-event-page data-event-id="{{ $match['id'] }}">
    <nav class="football-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('sports.football') }}">Football</a>
        <span aria-hidden="true">/</span>
        <span>{{ $match['home_team']['name'] }} vs {{ $match['away_team']['name'] }}</span>
    </nav>

    <section class="football-live-hero football-event-hero">
        <span class="rm-kicker">{{ $match['league']['name'] ?? 'Football' }}</span>
        <h1>{{ $match['home_team']['name'] }} vs {{ $match['away_team']['name'] }}</h1>
        <p>{{ $match['status'] }} @if($match['venue']) at {{ $match['venue'] }} @endif</p>
        <div class="football-event-score">
            <div class="football-event-team">
                <img src="{{ $match['home_team']['badge'] ?? asset('brand/rifi-logo.png') }}" alt="" loading="lazy">
                <strong>{{ $match['score']['home'] ?? '-' }}</strong>
            </div>
            <span>{{ $match['date'] }} {{ $match['time'] }}</span>
            <div class="football-event-team football-event-team--away">
                <strong>{{ $match['score']['away'] ?? '-' }}</strong>
                <img src="{{ $match['away_team']['badge'] ?? asset('brand/rifi-logo.png') }}" alt="" loading="lazy">
            </div>
        </div>
    </section>

    <section class="football-panel">
        <div class="football-panel__header">
            <div>
                <span class="rm-kicker">Broadcasts</span>
                <h2>Available TV channels</h2>
            </div>
            <a href="{{ route('sports.football') }}">Back to live scores</a>
        </div>
        <div data-event-tv-channels>
            <div class="football-state football-state--loading"><strong>Finding broadcast options</strong><p>Checking available TV channels for this match.</p></div>
        </div>
    </section>

    <section class="football-panel football-event-info">
        <div class="football-panel__header">
            <div>
                <span class="rm-kicker">Match info</span>
                <h2>Details</h2>
            </div>
        </div>
        <div class="rm-feature-grid">
            <article><strong>League</strong><p>{{ $match['league']['name'] ?? 'Football' }}</p></article>
            <article><strong>Kickoff</strong><p>{{ trim(($match['date'] ?? '').' '.($match['time'] ?? '')) ?: 'Time unavailable' }}</p></article>
            <article><strong>Status</strong><p>{{ $match['status'] ?? 'Match status unavailable' }}</p></article>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/football-live.js')
@endpush

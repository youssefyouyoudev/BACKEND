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
            <strong>{{ $match['score']['home'] ?? '-' }}</strong>
            <span>{{ $match['date'] }} {{ $match['time'] }}</span>
            <strong>{{ $match['score']['away'] ?? '-' }}</strong>
        </div>
    </section>

    <section class="football-panel">
        <div class="football-panel__header">
            <h2>TV channels</h2>
            <a href="{{ route('sports.football') }}">Back to live scores</a>
        </div>
        <div data-event-tv-channels>
            <p class="football-empty">Loading broadcast information...</p>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/football-live.js')
@endpush

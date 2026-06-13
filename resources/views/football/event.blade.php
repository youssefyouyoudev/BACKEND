@extends('layouts.app')

@section('title', ($match['home_team']['name'] ?? __('Match')).' '.__('vs').' '.($match['away_team']['name'] ?? __('Match')).' | '.__('Football Match Center'))
@section('description', __("Football match details, status, score, venue, and TV channel availability with direct watch links."))
@section('image', $match['home_team']['badge'] ?? asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<div class="rm-page football-live-page" data-football-event-page data-event-id="{{ $match['id'] }}">
    <nav class="football-breadcrumb" aria-label="{{ __("Breadcrumb") }}">
        <a href="{{ route('sports.football') }}">{{ __("Football") }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $match['home_team']['name'] }} vs {{ $match['away_team']['name'] }}</span>
    </nav>

    <section class="football-live-hero football-event-hero match-hero-card">
        <span class="rm-kicker">{{ $match['league']['name'] ?? __('Football') }}</span>
        <h1>{{ $match['home_team']['name'] }} vs {{ $match['away_team']['name'] }}</h1>
        <p>{{ $match['status'] }} @if($match['venue']) at {{ $match['venue'] }} @endif</p>
        <div class="football-event-score">
            <div class="football-event-team">
                <img src="{{ $match['home_team']['badge'] ?? asset('brand/rifi-logo.png') }}" alt="{{ $match['home_team']['name'] }} {{ app()->isLocale('ar') ? 'شعار الفريق' : 'team badge' }}" loading="lazy">
                <strong>{{ $match['score']['home'] ?? '-' }}</strong>
            </div>
            <span>{{ $match['date'] }} {{ $match['time'] }}</span>
            <div class="football-event-team football-event-team--away">
                <strong>{{ $match['score']['away'] ?? '-' }}</strong>
                <img src="{{ $match['away_team']['badge'] ?? asset('brand/rifi-logo.png') }}" alt="{{ $match['away_team']['name'] }} {{ app()->isLocale('ar') ? 'شعار الفريق' : 'team badge' }}" loading="lazy">
            </div>
        </div>
    </section>

    <section class="football-panel">
        <div class="football-panel__header">
            <div>
                <span class="rm-kicker">{{ __("Broadcasts") }}</span>
                <h2>{{ __("Available TV channels") }}</h2>
            </div>
            <a href="{{ route('sports.football') }}">{{ __("Back to live scores") }}</a>
        </div>
        <div data-event-tv-channels>
            <div class="football-state football-state--loading"><strong>{{ __("Finding broadcast options") }}</strong><p>{{ __("Checking available TV channels for this match.") }}</p></div>
        </div>
    </section>

    <section class="football-panel football-event-info">
        <div class="football-panel__header">
            <div>
                <span class="rm-kicker">{{ __("Match info") }}</span>
                <h2>{{ __("Details") }}</h2>
            </div>
        </div>
        <div class="rm-feature-grid">
            <article><strong>{{ __("League") }}</strong><p>{{ $match['league']['name'] ?? __('Football') }}</p></article>
            <article><strong>{{ __("Kickoff") }}</strong><p>{{ trim(($match['date'] ?? '').' '.($match['time'] ?? '')) ?: __('Time unavailable') }}</p></article>
            <article><strong>{{ __("Status") }}</strong><p>{{ $match['status'] ?? __('Match status unavailable') }}</p></article>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/football-live.js')
@endpush

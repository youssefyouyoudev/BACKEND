@extends('layouts.app')

@section('title', 'World Cup 2026 Knockout Schedule in Morocco Time | RiFiTV')
@section('description', 'Full World Cup 2026 knockout schedule from Round of 32 to Final, with Morocco time, stadiums, channels, and match pages.')
@section('image', asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<div class="wc-schedule-page wc-knockout-page">
    <section class="wc-schedule-hero wc-knockout-hero">
        <div>
            <span class="wc-badge"><b>{{ __('Knockout') }}</b> {{ __('Morocco Time') }}</span>
            <h1>{{ __('World Cup 2026 Road to Final') }}</h1>
            <p>{{ __('Round of 32 to Final') }} &mdash; {{ __('Morocco time') }}</p>
            <div class="wc-hero__actions">
                <a class="wc-button wc-button--primary" href="#road-to-final">{{ __('Road to Final') }}</a>
                <a class="wc-button wc-button--ghost" href="{{ route('world-cup-2026.schedule') }}">{{ __('Group-stage schedule') }}</a>
            </div>
        </div>
        <x-promo-banner compact />
    </section>

    <nav class="wc-schedule-tabs" aria-label="{{ __('World Cup 2026 schedule views') }}">
        <a href="{{ route('world-cup-2026.schedule') }}">{{ __('Group Stage') }}</a>
        <a class="is-active" href="{{ route('world-cup-2026.knockout') }}">{{ __('Knockout') }}</a>
        <a href="{{ route('world-cup-2026.road-to-final') }}">{{ __('Road to Final') }}</a>
    </nav>

    <nav class="wc-knockout-stage-tabs" aria-label="{{ __('Knockout stages') }}">
        @foreach($matchesByStage as $stage => $stageMatches)
            <a href="#stage-{{ $stage }}">{{ $stageMatches->first()?->public_stage_label }}</a>
        @endforeach
    </nav>

    <section id="road-to-final" class="wc-section">
        <div class="wc-section__heading">
            <div>
                <span class="wc-badge">{{ trans_choice('common.matches_count', $matches->count(), ['count' => $matches->count()]) }}</span>
                <h2 class="wc-title">{{ __('Road to Final') }}</h2>
                <p class="wc-subtitle">{{ __('Every knockout match is converted from stadium-local kickoff to Africa/Casablanca time.') }}</p>
            </div>
        </div>
        <x-road-to-final :matches-by-stage="$matchesByStage" />
    </section>

    <section class="wc-knockout-list" aria-labelledby="knockout-list-title">
        <div class="wc-section__heading">
            <div>
                <span class="wc-badge">{{ __('Schedule') }}</span>
                <h2 id="knockout-list-title" class="wc-title">{{ __('Knockout Matches') }}</h2>
            </div>
        </div>

        @forelse($matchesByStage as $stage => $stageMatches)
            <section class="wc-schedule-group" id="cards-{{ $stage }}">
                <div class="wc-schedule-group__heading">
                    <h3>{{ $stageMatches->first()?->public_stage_label }}</h3>
                    <span>{{ trans_choice('common.matches_count', $stageMatches->count(), ['count' => $stageMatches->count()]) }}</span>
                </div>
                <div class="wc-schedule-grid">
                    @foreach($stageMatches as $match)
                        <x-match-card :match="$match" variant="world-cup" />
                    @endforeach
                </div>
            </section>
        @empty
            <div class="wc-empty-state">
                <h3>{{ __('No knockout matches are available yet.') }}</h3>
                <p>{{ __('Run the knockout seeder to publish the Round of 32 through Final schedule.') }}</p>
            </div>
        @endforelse
    </section>
</div>
@endsection

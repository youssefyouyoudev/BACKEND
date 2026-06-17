@extends('layouts.app')

@section('title', 'Football TV Guide Morocco | RiFiTV')
@section('description', 'Football TV guide information with match schedules, competitions, Morocco kickoff times, broadcasters, commentators, and status updates.')

@section('content')
<div class="rm-page tv-guide-page">
    <nav class="football-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span>
        <span>{{ __('TV Guide') }}</span>
    </nav>

    <section class="rm-page-hero">
        <span class="rm-kicker"><x-icon name="tv" /> {{ __('Morocco time') }}</span>
        <h1>{{ __('Football TV guide') }}</h1>
        <p>{{ __('Match schedules, competition details, Morocco kickoff times, and official broadcaster information in one place.') }}</p>
    </section>

    <section class="rm-section tv-guide-table">
        <div class="rm-section__header">
            <div>
                <span class="rm-kicker">{{ __('Schedule') }}</span>
                <h2>{{ __('Upcoming football coverage') }}</h2>
            </div>
        </div>

        @forelse($matches as $match)
            <article class="tv-guide-row">
                <time datetime="{{ $match->kickoff_at_morocco?->toIso8601String() }}">
                    <strong>{{ $match->kickoff_at_morocco?->format('H:i') }}</strong>
                    <span>{{ $match->kickoff_at_morocco?->translatedFormat('M j') }}</span>
                </time>
                <div>
                    <small>{{ $match->competition }}{{ $match->stage ? ' - '.$match->stage : '' }}</small>
                    <strong>{{ $match->home_team }} vs {{ $match->away_team }}</strong>
                </div>
                <div>
                    <small>{{ __('Broadcaster') }}</small>
                    <strong>{{ $match->public_channel_name }}</strong>
                    @if($match->commentator)<span>{{ $match->commentator }}</span>@endif
                </div>
                <span class="football-status-badge football-status-badge--{{ $match->broadcast_status }}">
                    {{ str($match->broadcast_status)->headline() }}
                </span>
                <a class="rtv-button rtv-button--secondary" href="{{ route('matches.watch', $match) }}">{{ __('Match details') }}</a>
            </article>
        @empty
            <x-empty-state
                title="{{ __('No matches found for this date.') }}"
                message="{{ __('Official TV information has not been confirmed yet. Try another date.') }}"
                action="{{ __('View football schedules') }}"
                :href="route('football.schedules')"
            />
        @endforelse
    </section>

    <section class="rm-section rm-readable-card">
        <p>{{ __('RiFiTV provides football TV guide information, schedules, and match details. Broadcast availability may vary by country and provider.') }}</p>
    </section>
</div>
@endsection

@extends('layouts.app')

@section('title', "Match Center - {$match->home_display_name} vs {$match->away_display_name} | RiFiTV")
@section('description', "Follow {$match->home_display_name} vs {$match->away_display_name} with kickoff time, match details, qualification updates, TV guide information, and football coverage on RiFiTV.")
@section('robots', 'noindex,follow')
@section('image', $match->home_flag ?: asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<div class="match-watch-page">
    <nav class="football-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span>
        <a href="{{ route('world-cup-2026.index') }}">{{ __('World Cup 2026') }}</a><span>/</span>
        <span>{{ $match->home_display_name }} vs {{ $match->away_display_name }}</span>
    </nav>

    <section class="watch-layout">
        <div class="watch-main">
            <section class="match-watch-hero match-hero-card">
                <div class="match-watch-hero__topline">
                    <span>{{ $match->competition }}{{ $match->stage ? ' - '.$match->stage : '' }}</span>
                    <b class="match-window-badge match-window-badge--{{ $status === 'open' ? 'live' : ($status === 'expired' ? 'ended' : 'soon') }}">
                        {{ $status === 'open' ? __('live.label') : ($status === 'expired' ? 'FT' : __('Scheduled')) }}
                    </b>
                </div>
                <div class="match-scoreboard">
                    <div class="match-scoreboard__team">
                        <x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" />
                        <strong>{{ $match->home_display_name }}</strong>
                        @if($match->qualified_team === $match->home_team)
                            <small>{{ __('Qualified') }}</small>
                        @endif
                    </div>
                    <div class="match-scoreboard__center">
                        @if($match->hasQualifiedTeam())
                            <span>{{ $match->kickoff_at_morocco?->translatedFormat('D, M j') }}</span>
                            <b>{{ __('Qualified') }}</b>
                            <small>{{ __(':team qualified', ['team' => $match->qualified_team]) }}</small>
                        @else
                            <span>{{ $match->kickoff_at_morocco?->translatedFormat('D, M j') }}</span>
                            <b>{{ $match->kickoff_at_morocco?->format('H:i') }}</b>
                            <small>{{ __('Morocco time') }}</small>
                        @endif
                    </div>
                    <div class="match-scoreboard__team">
                        <x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" />
                        <strong>{{ $match->away_display_name }}</strong>
                        @if($match->qualified_team === $match->away_team)
                            <small>{{ __('Qualified') }}</small>
                        @endif
                    </div>
                </div>
            </section>

            @if(! $match->is_live_link_enabled)
                <section class="match-watch-state">
                    <span class="rm-kicker">{{ __('Match coverage') }}</span>
                    <h2>{{ __('Broadcast options are not available yet.') }}</h2>
                    <p>{{ __('Match information and official TV details will be updated when confirmed.') }}</p>
                </section>
            @elseif($status === 'opens_soon')
                <section class="match-watch-state">
                    <span class="rm-kicker">{{ __('Match center') }}</span>
                    <h2>{{ __('Available options open at') }} {{ $match->watch_opens_at?->format('H:i') }}</h2>
                    <p>{{ __('Morocco time') }}</p>
                    <div class="match-countdown" data-countdown data-countdown-target="{{ $match->watch_opens_at?->toIso8601String() }}">
                        @foreach(['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $field => $label)
                            <span><b data-countdown-{{ $field }}>00</b><small>{{ __($label) }}</small></span>
                        @endforeach
                    </div>
                </section>
            @elseif($status === 'open')
                <section class="match-watch-player">
                    @if($sources->isNotEmpty())
                        <x-video-player :channel="$match" :sources="$sources" :poster="$match->home_flag ?: $match->away_flag" />
                    @elseif($manualWatchUrl)
                        <a class="rtv-button rtv-button--primary" href="{{ $manualWatchUrl }}" target="_blank" rel="nofollow noopener noreferrer">
                            <x-icon name="play" /> {{ __('Open match') }}
                        </a>
                    @else
                        <div class="match-watch-empty">{{ __('Match information is not available yet.') }}</div>
                    @endif
                </section>
            @else
                <section class="match-watch-state match-watch-state--ended">
                    <span class="rm-kicker">{{ __('Match coverage') }}</span>
                    <h2>{{ __('This match has ended.') }}</h2>
                    <p>{{ __('Choose an upcoming match below.') }}</p>
                </section>
            @endif

            <x-ad-slot name="match_watch_under_content" type="inline" compact />
        </div>

        <aside class="watch-sidebar">
            <section class="match-info-card">
                <span class="rm-kicker">{{ __('Match info') }}</span>
                <h2>{{ $match->home_display_name }} vs {{ $match->away_display_name }}</h2>
                <dl>
                    @if($match->hasQualifiedTeam())
                        <div><dt>{{ __('Qualification') }}</dt><dd>{{ __(':team qualified', ['team' => $match->qualified_team]) }}</dd></div>
                    @endif
                    <div><dt>{{ __('Kickoff') }}</dt><dd>{{ $match->kickoff_at_morocco?->translatedFormat('M j - H:i') }} {{ __('Morocco') }}</dd></div>
                    <div><dt>{{ __('Competition') }}</dt><dd>{{ $match->competition }}{{ $match->stage ? ' - '.$match->stage : '' }}</dd></div>
                    <div><dt>{{ __('TV info') }}</dt><dd>{{ $match->public_channel_name }}</dd></div>
                    @if($match->venue)
                        <div><dt>{{ __('Venue') }}</dt><dd>{{ collect([$match->venue, $match->city])->filter()->implode(', ') }}</dd></div>
                    @endif
                    @if($match->commentator)
                        <div><dt>{{ __('Commentator') }}</dt><dd>{{ $match->commentator }}</dd></div>
                    @endif
                </dl>
            </section>

            <x-ad-slot name="match_watch_sidebar" type="sidebar" compact />

            @if($upcomingMatches->isNotEmpty())
                <section class="match-watch-upcoming">
                    <div class="rtv-section-heading">
                        <div><span class="rtv-kicker">{{ __('Up next') }}</span><h2>{{ __('Upcoming matches') }}</h2></div>
                    </div>
                    <div class="watch-related-list">
                        @foreach($upcomingMatches as $upcoming)
                            <a class="match-watch-suggestion" href="{{ route('matches.watch', $upcoming) }}">
                                <span>{{ $upcoming->competition }}</span>
                                <strong>{{ $upcoming->home_display_name }} <small>vs</small> {{ $upcoming->away_display_name }}</strong>
                                <b>{{ $upcoming->kickoff_at_morocco?->translatedFormat('M j - H:i') }} {{ __('Morocco') }}</b>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </section>
</div>
@endsection

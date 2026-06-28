@props(['match', 'variant' => null])

@php
    $isWorldCupMatch = $match instanceof \App\Models\WorldCupMatch || $variant === 'world-cup';
@endphp

@if($isWorldCupMatch)
    @php
        $kickoff = $match->kickoff_at_morocco;
        $badges = $match->broadcast_badges;
    @endphp

    <article {{ $attributes->merge(['class' => 'wc-schedule-card match-card']) }}>
        <header>
            <span>{{ __('Match') }} {{ $match->match_number }} · {{ $match->public_stage_label }}</span>
            <span class="wc-match-status wc-match-status--{{ $match->broadcast_status }}">{{ str($match->broadcast_status)->headline() }}</span>
        </header>
        <div class="wc-schedule-card__time">
            <strong>{{ $kickoff?->format('H:i') ?? '--:--' }}</strong>
            <span>{{ $kickoff?->translatedFormat('D, M d') }} · {{ __('Morocco Time') }}</span>
        </div>
        <div class="wc-schedule-card__teams">
            <strong><x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" /><span>{{ $match->home_display_name }}</span></strong>
            <span>VS</span>
            <strong><x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" /><span>{{ $match->away_display_name }}</span></strong>
        </div>
        @if($match->venue || $match->city)
            <p class="wc-schedule-card__venue">{{ collect([$match->venue, $match->city])->filter()->implode(' · ') }}</p>
        @endif
        <div class="wc-schedule-card__details">
            <p>
                <b>{{ __('Channel') }}</b>
                <span class="wc-channel-badges">
                    @forelse($badges as $badge)
                        <em>{{ $badge }}</em>
                    @empty
                        <em>{{ __('Channels to be confirmed') }}</em>
                    @endforelse
                </span>
            </p>
            <p><b>{{ __('Commentator') }}</b><span>{{ $match->commentator ?: __('Commentator to be confirmed') }}</span></p>
        </div>
        <a class="wc-button wc-button--primary wc-schedule-card__watch" href="{{ route('matches.watch', $match) }}">
            {{ __('Open Match') }}
        </a>
    </article>
@else
@php
    $home = data_get($match, 'home_team.name', 'Home');
    $away = data_get($match, 'away_team.name', 'Away');
    $scoreHome = data_get($match, 'score.home');
    $scoreAway = data_get($match, 'score.away');
    $score = $scoreHome !== null && $scoreAway !== null ? "{$scoreHome} - {$scoreAway}" : (data_get($match, 'time') ?: __('TBD'));
    $url = data_get($match, 'event_url') ?: (data_get($match, 'id') ? route('sports.football.event', data_get($match, 'id')) : route('sports.football'));
@endphp

<article {{ $attributes->merge(['class' => 'football-match-card match-card']) }}>
    <header class="football-match-card__header">
        <span><x-icon name="trophy" /> {{ data_get($match, 'league.name', 'Football') }}</span>
        <b class="football-status-badge football-status-badge--{{ data_get($match, 'status_type', 'unknown') }}">{{ data_get($match, 'status', 'Unknown') }}</b>
    </header>
    <div class="football-scoreline">
        <div class="football-team">
            <img src="{{ data_get($match, 'home_team.badge') ?: asset('brand/rifi-logo.png') }}" alt="{{ $home }} {{ app()->isLocale('ar') ? 'شعار الفريق' : 'team badge' }}" loading="lazy">
            <strong>{{ $home }}</strong>
        </div>
        <a href="{{ $url }}" class="football-scoreline__score">{{ $score }}</a>
        <div class="football-team football-team--away">
            <img src="{{ data_get($match, 'away_team.badge') ?: asset('brand/rifi-logo.png') }}" alt="{{ $away }} {{ app()->isLocale('ar') ? 'شعار الفريق' : 'team badge' }}" loading="lazy">
            <strong>{{ $away }}</strong>
        </div>
    </div>
</article>
@endif

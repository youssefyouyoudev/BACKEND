@props(['match'])

@php
    $home = data_get($match, 'home_team.name', 'Home');
    $away = data_get($match, 'away_team.name', 'Away');
    $scoreHome = data_get($match, 'score.home');
    $scoreAway = data_get($match, 'score.away');
    $score = $scoreHome !== null && $scoreAway !== null ? "{$scoreHome} - {$scoreAway}" : (data_get($match, 'time') ?: 'TBD');
    $url = data_get($match, 'event_url') ?: (data_get($match, 'id') ? route('sports.football.event', data_get($match, 'id')) : route('sports.football'));
@endphp

<article {{ $attributes->merge(['class' => 'football-match-card']) }}>
    <header class="football-match-card__header">
        <span>{{ data_get($match, 'league.name', 'Football') }}</span>
        <b class="football-status-badge football-status-badge--{{ data_get($match, 'status_type', 'unknown') }}">{{ data_get($match, 'status', 'Unknown') }}</b>
    </header>
    <div class="football-scoreline">
        <div class="football-team">
            <img src="{{ data_get($match, 'home_team.badge') ?: asset('brand/rifi-logo.png') }}" alt="" loading="lazy">
            <strong>{{ $home }}</strong>
        </div>
        <a href="{{ $url }}" class="football-scoreline__score">{{ $score }}</a>
        <div class="football-team football-team--away">
            <img src="{{ data_get($match, 'away_team.badge') ?: asset('brand/rifi-logo.png') }}" alt="" loading="lazy">
            <strong>{{ $away }}</strong>
        </div>
    </div>
</article>

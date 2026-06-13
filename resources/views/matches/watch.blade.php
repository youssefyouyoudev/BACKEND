@extends('layouts.app')

@section('title', "{$match->home_team} vs {$match->away_team} - Watch Match - RiFiTV")
@section('description', "Watch {$match->home_team} vs {$match->away_team} match info, kickoff time, channel and commentator in Morocco time on RiFiTV.")
@section('image', $match->home_flag ?: asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<div class="match-watch-page">
    <nav class="football-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span>
        <a href="{{ route('world-cup.index') }}">{{ __('Matches') }}</a><span>/</span>
        <span>{{ $match->home_team }} vs {{ $match->away_team }}</span>
    </nav>

    <section class="match-watch-hero match-hero-card">
        <div class="match-watch-hero__topline">
            <span>{{ $match->competition }}{{ $match->stage ? ' - '.$match->stage : '' }}</span>
            <b class="match-window-badge match-window-badge--{{ $status === 'open' ? 'live' : ($status === 'expired' ? 'ended' : 'soon') }}">
                {{ $status === 'open' ? __('Live Now') : ($status === 'expired' ? __('Match Ended') : __('Opens Soon')) }}
            </b>
        </div>
        <div class="match-scoreboard">
            <div class="match-scoreboard__team"><x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" /><strong>{{ $match->home_team }}</strong></div>
            <div class="match-scoreboard__center"><span>{{ $match->kickoff_at_morocco?->translatedFormat('D, M j') }}</span><b>{{ $match->kickoff_at_morocco?->format('H:i') }}</b><small>{{ __('Morocco Time') }}</small></div>
            <div class="match-scoreboard__team"><x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" /><strong>{{ $match->away_team }}</strong></div>
        </div>
        <div class="match-watch-facts">
            @if($match->venue)<span><x-icon name="location" /> {{ collect([$match->venue, $match->city])->filter()->implode(', ') }}</span>@endif
            <span><x-icon name="tv" /> {{ $match->public_channel_name }}</span>
            @if($match->commentator)<span><x-icon name="user" /> {{ $match->commentator }}</span>@endif
        </div>
    </section>

    <x-ad-slot name="match_watch_before_content" type="inline" compact />

    @if(! $match->is_live_link_enabled)
        <section class="match-watch-player">
            <div class="match-watch-empty">{{ __('Watch links will appear here before kickoff.') }}</div>
        </section>
    @elseif($status === 'opens_soon')
        <section class="match-watch-state">
            <span class="rm-kicker">{{ __('Watch window') }}</span>
            <h2>{{ __('Watch page opens at') }} {{ $match->watch_opens_at?->format('H:i') }}</h2>
            <p>{{ __('Morocco time') }}</p>
            <div class="match-countdown" data-countdown data-countdown-target="{{ $match->watch_opens_at?->toIso8601String() }}">
                @foreach(['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $field => $label)
                    <span><b data-countdown-{{ $field }}>00</b><small>{{ __($label) }}</small></span>
                @endforeach
            </div>
            <button type="button" class="rtv-button rtv-button--secondary" disabled><x-icon name="clock" /> {{ __('Add to reminder') }}</button>
        </section>
    @elseif($status === 'open')
        <section class="match-watch-player">
            <div class="match-watch-player__heading">
                <div><span class="rm-kicker">{{ __('Watch live') }}</span><h2>{{ $match->home_team }} vs {{ $match->away_team }}</h2></div>
                <small>{{ __('This server is not available. Try another server.') }}</small>
            </div>
            @if($sources->isNotEmpty())
                @php($activeSource = $sources->first())
                <div class="match-player-frame" data-match-embed-player>
                    <iframe
                        src="{{ $activeSource['embed_url'] }}"
                        class="match-player-iframe"
                        allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
                        allowfullscreen
                        referrerpolicy="no-referrer"
                        loading="eager"
                        title="{{ $activeSource['title'] ?? $match->home_team.' vs '.$match->away_team }}"
                    ></iframe>
                    <div class="match-player-fallback" data-match-embed-error hidden>
                        {{ __('This server is not available. Try another server.') }}
                    </div>
                </div>
                <div class="match-watch-servers" role="list" aria-label="{{ __('Stream servers') }}">
                    @foreach($sources as $index => $source)
                        <button
                            type="button"
                            class="{{ $index === 0 ? 'is-active' : '' }}"
                            data-match-embed-source="{{ $source['embed_url'] }}"
                            data-match-embed-title="{{ $source['title'] ?? $source['channel'] ?? __('Server :number', ['number' => $index + 1]) }}"
                        >
                            <strong>{{ $source['label'] ?? __('Server :number', ['number' => $index + 1]) }}</strong>
                            <span>{{ collect([$source['quality'] ?? null, $source['channel'] ?? null])->filter()->implode(' - ') }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="match-watch-empty">{{ __('Watch links will appear here before kickoff.') }}</div>
            @endif
        </section>
    @else
        <section class="match-watch-state match-watch-state--ended">
            <span class="rm-kicker">{{ __('Watch window closed') }}</span>
            <h2>{{ __('This match has ended.') }}</h2>
            <p>{{ __('Choose an upcoming match below.') }}</p>
        </section>
    @endif

    <x-ad-slot name="match_watch_under_content" type="inline" compact />
    <section class="match-watch-premium">
        <span><x-icon name="message" /></span>
        <div><strong>{{ __('For premium quality contact us on WhatsApp.') }}</strong><small>RiFiMedia - 0663323824</small></div>
        <a href="https://wa.me/212663323824" target="_blank" rel="noopener noreferrer">{{ __('Contact us') }}</a>
    </section>

    @if($upcomingMatches->isNotEmpty())
        <section class="match-watch-upcoming">
            <div class="rtv-section-heading"><div><span class="rtv-kicker">{{ __('Up next') }}</span><h2>{{ __('Upcoming Matches') }}</h2></div></div>
            <div class="rtv-match-grid">
                @foreach($upcomingMatches as $upcoming)
                    <a class="match-watch-suggestion" href="{{ route('matches.watch', $upcoming) }}">
                        <span>{{ $upcoming->competition }}</span>
                        <strong>{{ $upcoming->home_team }} <small>vs</small> {{ $upcoming->away_team }}</strong>
                        <b>{{ $upcoming->kickoff_at_morocco?->translatedFormat('M j - H:i') }} {{ __('Morocco') }}</b>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-match-embed-player]').forEach((player) => {
        const iframe = player.querySelector('.match-player-iframe');
        const error = player.querySelector('[data-match-embed-error]');
        const buttons = [...document.querySelectorAll('[data-match-embed-source]')];

        if (! iframe) return;

        iframe.addEventListener('error', () => {
            if (error) error.hidden = false;
        });

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const nextSource = button.dataset.matchEmbedSource;
                if (! nextSource || iframe.getAttribute('src') === nextSource) return;

                if (error) error.hidden = true;
                buttons.forEach((candidate) => candidate.classList.toggle('is-active', candidate === button));
                iframe.removeAttribute('src');
                iframe.src = nextSource;
                iframe.title = button.dataset.matchEmbedTitle || iframe.title;
            });
        });
    });
});
</script>
@endpush
@endsection

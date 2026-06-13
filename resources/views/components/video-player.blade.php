@props([
    'channel' => null,
    'sources' => [],
    'poster' => null,
    'autoplay' => true,
])

@php
    $playerId = 'rifi-player-'.uniqid();
    $payload = [
        'matchId' => data_get($channel, 'id'),
        'title' => data_get($channel, 'name')
            ?: trim(data_get($channel, 'home_team').' vs '.data_get($channel, 'away_team')),
        'sources' => collect($sources)->values()->all(),
        'poster' => $poster,
        'autoplay' => $autoplay,
    ];
@endphp

<section class="rifitv-match-player" data-match-player data-player-id="{{ $playerId }}">
    <script type="application/json" data-match-player-config>@json($payload)</script>

    <header class="rifitv-player-now">
        <div>
            <span class="rifitv-player-now__eyebrow">{{ __('Now playing') }}</span>
            <strong data-match-player-channel>{{ __('Preparing stream...') }}</strong>
            <small data-match-player-meta></small>
        </div>
        <span class="rifitv-player-health" data-match-player-health>{{ __('Loading') }}</span>
    </header>

    <div class="rifitv-player-shell">
        <video
            id="{{ $playerId }}"
            class="rifitv-player-video"
            controls
            playsinline
            preload="auto"
            @if($poster) poster="{{ $poster }}" @endif
        ></video>

        <iframe
            class="rifitv-player-iframe"
            data-match-player-iframe
            title="{{ __('Live match player') }}"
            allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
            allowfullscreen
            referrerpolicy="no-referrer"
            loading="eager"
            hidden
        ></iframe>

        <div class="rifitv-player-overlay" data-match-player-overlay role="status" aria-live="polite">
            <span class="rifitv-player-spinner"></span>
            <strong data-match-player-state-title>{{ __('Preparing stream...') }}</strong>
            <p data-match-player-state-message>{{ __('Building a stable buffer for smoother playback.') }}</p>
        </div>

        <button type="button" class="rifitv-player-play" data-match-player-play aria-label="{{ __('Play stream') }}" hidden>
            <x-icon name="play" />
        </button>
    </div>

    <div class="rifitv-player-controls" aria-label="{{ __('Player controls') }}">
        <button type="button" data-match-player-reload><x-icon name="signal" /> {{ __('Reload stream') }}</button>
        <button type="button" data-match-player-next><x-icon name="arrow-right" /> {{ __('Try next server') }}</button>
        <button type="button" data-match-player-mute><x-icon name="volume" /> {{ __('Mute / unmute') }}</button>
        <button type="button" data-match-player-fullscreen><x-icon name="tv" /> {{ __('Fullscreen') }}</button>
        <a href="#" data-match-player-external target="_blank" rel="nofollow noopener noreferrer" hidden>
            <x-icon name="arrow-up-right" /> {{ __('Open external stream') }}
        </a>
    </div>

    <div class="rifitv-player-options">
        <section>
            <div class="rifitv-player-options__heading">
                <div><span>{{ __('Available channels') }}</span><strong>{{ __('Choose a broadcast') }}</strong></div>
                <small>{{ __('The recommended option is selected first.') }}</small>
            </div>
            <div class="rifitv-channel-tabs" data-match-player-channels role="tablist"></div>
        </section>

        <section>
            <div class="rifitv-player-options__heading">
                <div><span>{{ __('Servers and quality') }}</span><strong data-match-player-server-heading>{{ __('Select a server') }}</strong></div>
            </div>
            <div class="rifitv-server-grid" data-match-player-servers></div>
        </section>
    </div>
</section>

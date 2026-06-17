@extends('layouts.app')

@section('title', $item->name.' - Available Option | RiFiTV')
@section('robots', 'noindex,nofollow')

@section('content')
    <section class="iptv-player-page" data-iptv-player-page>
        <aside class="iptv-channel-list" data-focus-list>
            <a href="{{ route('watch.index') }}" class="button button--ghost">{{ __("Back") }}</a>
            <h2>{{ $item->category?->name ?? str($item->type)->headline() }}</h2>
            <div>
                @foreach($siblings as $sibling)
                    <a href="{{ route('watch.item', $sibling) }}" class="iptv-channel-link {{ $sibling->is($item) ? 'is-active' : '' }}" data-focusable>
                        {{ $sibling->name }}
                    </a>
                @endforeach
            </div>
        </aside>

        <main class="iptv-player-main">
            <div class="iptv-video-shell">
                <video
                    data-iptv-player
                    data-stream-url="{{ $browserUrl }}"
                    data-player-sources="{{ $playerSources->toJson() }}"
                    data-history-url="{{ route('watch.history', $item) }}"
                    data-stream-type="{{ $item->extension }}"
                    data-channel-id="{{ $item->id }}"
                    controls
                    playsinline
                    preload="metadata"
                    poster="{{ $item->logo }}"
                ></video>
                <x-video-premium-ticker />
                <div class="iptv-player-state" data-player-state>
                    <span class="iptv-spinner"></span>
                    <h3 data-player-state-title hidden></h3>
                    <p>{{ __("Loading stream...") }}</p>
                    <div class="iptv-player-state__actions">
                        <button type="button" class="button button--ghost" data-player-retry hidden>{{ __("Retry same source") }}</button>
                        <button type="button" class="button button--ghost" data-player-backup hidden>{{ __("Try backup source") }}</button>
                        <a class="button button--ghost" href="{{ route('watch.index') }}">{{ __("Choose another channel") }}</a>
                        <a class="button button--ghost" href="{{ route('contact') }}">{{ __("Report issue") }}</a>
                    </div>
                </div>
            </div>

            @if($playerSources->count() > 1)
                <div class="iptv-player-source-picker" data-iptv-source-picker>
                    <button type="button" data-source-index="0" class="is-active">
                        <strong>{{ __("Auto") }}</strong>
                        <small>{{ __("Recommended") }}</small>
                    </button>
                    @foreach($playerSources as $index => $source)
                        <button type="button" data-source-index="{{ $index }}" class="{{ $index === 0 ? 'is-active' : '' }}">
                            <strong>{{ $source['label'] }}</strong>
                            <small>{{ $source['quality'] }}</small>
                        </button>
                    @endforeach
                </div>
            @endif

            @if(auth()->user()?->isAdmin() || config('app.debug'))
                <details class="player-debug-panel">
                    <summary>{{ __("Player debug") }}</summary>
                    <pre data-player-debug></pre>
                </details>
            @endif

            <section class="iptv-player-meta">
                <div>
                    <p class="rm-eyebrow">{{ str($item->type)->headline() }}</p>
                    <h1>{{ $item->name }}</h1>
                    @if($item->description)
                        <p>{{ $item->description }}</p>
                    @endif
                </div>
                @auth
                    <form method="POST" action="{{ route('watch.favorite', $item) }}">
                        @csrf
                        <button class="button button--primary">{{ __("Favorite") }}</button>
                    </form>
                @endauth
            </section>
        </main>
    </section>
@endsection

@extends('layouts.app')

@section('title', $item->name.' - RifiMedia Player')

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
                    data-history-url="{{ route('watch.history', $item) }}"
                    data-stream-type="{{ $item->extension }}"
                    controls
                    playsinline
                    preload="metadata"
                    poster="{{ $item->logo }}"
                ></video>
                <x-video-premium-ticker />
                <div class="iptv-player-state" data-player-state>
                    <span class="iptv-spinner"></span>
                    <p>{{ __("Loading stream...") }}</p>
                    <button type="button" class="button button--ghost" data-player-retry hidden>{{ __("Retry") }}</button>
                </div>
            </div>

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

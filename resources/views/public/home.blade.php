@php
    $heroPayload = $heroChannel ? [
        'id' => $heroChannel->id,
        'name' => $heroChannel->name,
        'logo' => $heroChannel->logo ?: asset('brand/rifi-logo.png'),
        'category' => $heroChannel->group_title ?: 'Featured',
        'playlist' => $heroChannel->playlist?->name ?? 'Approved playlist',
    ] : null;
@endphp

@extends('layouts.app')

@section('content')
<div class="clean-home">
    <header class="clean-home__top">
        <x-logo />
        <form action="{{ route('home') }}" method="GET" class="clean-home__search">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search channels">
        </form>
        <a href="{{ route('admin.login') }}">Admin</a>
    </header>

    @if($heroPayload)
        <section class="clean-hero">
            <img src="{{ $heroPayload['logo'] }}" alt="" loading="lazy" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
            <div class="clean-hero__shade"></div>
            <div class="clean-hero__content">
                <span>Live Featured</span>
                <h1>{{ $heroPayload['name'] }}</h1>
                <p>{{ $heroPayload['category'] }} | {{ $heroPayload['playlist'] }}</p>
                <a href="{{ route('channels.show', $heroPayload['id']) }}">Watch</a>
            </div>
        </section>
    @else
        <section class="clean-hero clean-hero--empty">
            <div class="clean-hero__content">
                <span>RIFI Media TV</span>
                <h1>Build a premium IPTV library.</h1>
                <p>Approve a public playlist to start showing featured live streams here.</p>
            </div>
        </section>
    @endif

    <section class="clean-live-section">
        <div class="clean-section-heading">
            <div>
                <span>Now streaming</span>
                <h2>Live Channels</h2>
            </div>
            <a href="{{ route('live') }}">Open live view</a>
        </div>

        @if($channels->count() === 0)
            <div class="clean-empty">
                No channels match this view.
            </div>
        @else
            <div class="clean-channel-grid">
                @foreach($channels as $channel)
                    <article class="clean-card">
                        <a href="{{ route('channels.show', $channel) }}">
                            <span class="clean-card__thumb">
                                <img
                                    src="{{ $channel->logo ?: asset('brand/rifi-logo.png') }}"
                                    alt="{{ $channel->name }}"
                                    loading="lazy"
                                    onerror="this.src='{{ asset('brand/rifi-logo.png') }}'"
                                >
                                <em>Live</em>
                            </span>
                            <span class="clean-card__body">
                                <strong>{{ $channel->name }}</strong>
                                <small>{{ $channel->group_title ?: 'General' }}</small>
                            </span>
                        </a>
                    </article>
                @endforeach
            </div>

            {{ $channels->links() }}
        @endif
    </section>
</div>
@endsection

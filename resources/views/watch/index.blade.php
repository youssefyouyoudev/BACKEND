@extends('layouts.app')

@section('title', 'Watch - RifiMedia IPTV Player')

@section('content')
    <section class="iptv-shell">
        <aside class="iptv-sidebar">
            <x-logo />
            <nav>
                <a href="{{ route('watch.live') }}">Live TV</a>
                <a href="{{ route('watch.movies') }}">Movies</a>
                <a href="{{ route('watch.series') }}">Series</a>
                <a href="{{ route('watch.search') }}">Search</a>
                <a href="{{ route('admin.playlists.index') }}">Settings</a>
            </nav>
        </aside>

        <div class="iptv-main">
            <header class="iptv-hero">
                <div>
                    <p class="rm-eyebrow">Your playlists only</p>
                    <h1>Watch your IPTV library.</h1>
                    <p>No built-in channels. Add your own legal M3U, Xtream, active code, or uploaded playlist from the admin panel.</p>
                </div>
                <form action="{{ route('watch.search') }}" class="iptv-search">
                    <input name="q" placeholder="Search channels, movies, series">
                    <button class="button button--primary">Search</button>
                </form>
            </header>

            @if($continueWatching->isNotEmpty())
                <section class="iptv-row">
                    <div class="iptv-row__header">
                        <h2>Continue Watching</h2>
                    </div>
                    <div class="iptv-card-grid iptv-card-grid--rail">
                        @foreach($continueWatching as $item)
                            @include('watch.partials.item-card', ['item' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            @include('watch.partials.category-row', ['title' => 'Live TV', 'categories' => $liveCategories])
            @include('watch.partials.category-row', ['title' => 'Movies', 'categories' => $movieCategories])
            @include('watch.partials.category-row', ['title' => 'Series', 'categories' => $seriesCategories])
        </div>
    </section>
@endsection

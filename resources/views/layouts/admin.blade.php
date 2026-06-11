<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'RiFi Media TV Admin' }}</title>
    <meta name="description" content="RiFi Media TV administration dashboard for playlist management.">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body app-body--admin">
    <div class="shell shell--admin">
        <aside class="sidebar sidebar--admin">
            <div class="sidebar__brand">
                <x-logo :href="route('admin.dashboard')" />
                <span class="sidebar__panel-tag">Control Center</span>
            </div>

            <nav class="sidebar__nav" aria-label="Admin navigation">
                <a href="{{ route('admin.dashboard') }}" class="sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">▦</span>
                    <span>Dashboard</span>
                </a>
                <a href="#playlist-form" class="sidebar__link">
                    <span class="sidebar__icon">＋</span>
                    <span>Add Playlist</span>
                </a>
                <a href="#playlist-table" class="sidebar__link">
                    <span class="sidebar__icon">☰</span>
                    <span>Playlists</span>
                </a>
                <a href="{{ route('admin.iptv-items.index') }}" class="sidebar__link {{ request()->routeIs('admin.iptv-items.*') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">TV</span>
                    <span>IPTV Items</span>
                </a>
                <a href="{{ route('admin.world-cup-matches.index') }}" class="sidebar__link {{ request()->routeIs('admin.world-cup-matches.*') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">WC</span>
                    <span>World Cup Matches</span>
                </a>
                <a href="{{ route('admin.world-cup-matches.index', ['missing_channel' => 1]) }}" class="sidebar__link">
                    <span class="sidebar__icon">?</span>
                    <span>Missing Channels</span>
                </a>
                <a href="{{ route('admin.world-cup-matches.index', ['missing_commentator' => 1]) }}" class="sidebar__link">
                    <span class="sidebar__icon">Mic</span>
                    <span>Missing Commentators</span>
                </a>
                <a href="{{ route('home') }}" class="sidebar__link">
                    <span class="sidebar__icon">↗</span>
                    <span>Open Player</span>
                </a>
            </nav>

            <form method="POST" action="{{ route('admin.logout') }}" class="sidebar__logout">
                @csrf
                <button type="submit" class="button button--ghost button--full">Sign out</button>
            </form>
        </aside>

        <main class="main-content">
            <x-flash />
            @yield('content')
        </main>
    </div>
</body>
</html>

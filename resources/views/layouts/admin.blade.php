<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.theme-init')
    <title>{{ $title ?? __("RiFi Media TV Admin") }}</title>
    <meta name="description" content="{{ __("RiFi Media TV administration dashboard for playlist management.") }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])
</head>
<body class="app-body app-body--admin">
    <button type="button" class="admin-theme-toggle" data-theme-toggle aria-label="{{ __("Switch theme") }}" title="{{ __("Switch theme") }}">
        <span class="rm-theme-icon rm-theme-icon--moon" aria-hidden="true"><x-icon name="moon" /></span>
        <span class="rm-theme-icon rm-theme-icon--sun" aria-hidden="true"><x-icon name="sun" /></span>
    </button>
    <div class="shell shell--admin">
        <aside class="sidebar sidebar--admin">
            <div class="sidebar__brand">
                <x-logo :href="route('admin.dashboard')" />
                <span class="sidebar__panel-tag">{{ __("Control Center") }}</span>
            </div>

            <nav class="sidebar__nav" aria-label="{{ __("Admin navigation") }}">
                <a href="{{ route('admin.dashboard') }}" class="sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">▦</span>
                    <span>{{ __("Dashboard") }}</span>
                </a>
                <a href="#playlist-form" class="sidebar__link">
                    <span class="sidebar__icon">＋</span>
                    <span>{{ __("Add Playlist") }}</span>
                </a>
                <a href="#playlist-table" class="sidebar__link">
                    <span class="sidebar__icon">☰</span>
                    <span>{{ __("Playlists") }}</span>
                </a>
                <a href="{{ route('admin.iptv-items.index') }}" class="sidebar__link {{ request()->routeIs('admin.iptv-items.*') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">TV</span>
                    <span>{{ __("IPTV Items") }}</span>
                </a>
                <a href="{{ route('admin.world-cup-matches.index') }}" class="sidebar__link {{ request()->routeIs('admin.world-cup-matches.*') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">WC</span>
                    <span>{{ __("World Cup Matches") }}</span>
                </a>
                <a href="{{ route('admin.world-cup-matches.index', ['missing_channel' => 1]) }}" class="sidebar__link">
                    <span class="sidebar__icon">?</span>
                    <span>{{ __("Missing Channels") }}</span>
                </a>
                <a href="{{ route('admin.world-cup-matches.index', ['missing_commentator' => 1]) }}" class="sidebar__link">
                    <span class="sidebar__icon">{{ __("Mic") }}</span>
                    <span>{{ __("Missing Commentators") }}</span>
                </a>
                <a href="{{ route('home') }}" class="sidebar__link">
                    <span class="sidebar__icon">↗</span>
                    <span>{{ __("Open Player") }}</span>
                </a>
            </nav>

            <form method="POST" action="{{ route('admin.logout') }}" class="sidebar__logout">
                @csrf
                <button type="submit" class="button button--ghost button--full">{{ __("Sign out") }}</button>
            </form>
        </aside>

        <main class="main-content">
            <x-flash />
            @yield('content')
        </main>
    </div>
</body>
</html>

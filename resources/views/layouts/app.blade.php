<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'RiFi Media TV' }}</title>
    <meta name="description" content="{{ $description ?? 'Premium live sports and TV streaming with the RiFi Media experience.' }}">
    <meta property="og:title" content="{{ $title ?? 'RiFi Media TV' }}">
    <meta property="og:description" content="{{ $description ?? 'Watch live channels, featured broadcasts, and curated sports streams on RiFi Media TV.' }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js"></script>
</head>
<body class="app-body rm-body">
    @if (! empty($appSettings['maintenance_banner']))
        <div class="rm-maintenance" role="status">
            {{ $appSettings['maintenance_banner'] }}
        </div>
    @endif

    <div class="rm-site" x-data="{ mobileNavOpen: false }">
        <header class="rm-navbar" aria-label="Primary navigation">
            <div class="rm-navbar__inner">
                <x-logo />

                <nav class="rm-navbar__links" aria-label="Main menu">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                    <a href="{{ route('live') }}" class="{{ request()->routeIs('live') ? 'is-active' : '' }}">
                        <span class="rm-live-dot" aria-hidden="true"></span>
                        Live
                    </a>
                    <a href="{{ route('home') }}#categories">Sports</a>
                    <a href="{{ route('home') }}#channels">Channels</a>
                </nav>

                <div class="rm-navbar__actions">
                    <a href="{{ route('admin.login') }}" class="rm-btn rm-btn-secondary rm-btn-sm">Admin</a>
                    <a href="{{ route('live') }}" class="rm-btn rm-btn-primary rm-btn-sm">Watch Now</a>
                    <button
                        type="button"
                        class="rm-mobile-nav"
                        aria-label="Open navigation"
                        :aria-expanded="mobileNavOpen.toString()"
                        @click="mobileNavOpen = ! mobileNavOpen"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>

            <nav class="rm-navbar__drawer" x-show="mobileNavOpen" x-transition.opacity.origin.top @click.outside="mobileNavOpen = false" aria-label="Mobile menu">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                <a href="{{ route('live') }}" class="{{ request()->routeIs('live') ? 'is-active' : '' }}">Live TV</a>
                <a href="{{ route('home') }}#categories">Sports and Categories</a>
                <a href="{{ route('home') }}#channels">Channel Wall</a>
                <a href="{{ route('admin.login') }}">Admin</a>
            </nav>
        </header>

        <main class="rm-main">
            <x-flash />
            @yield('content')
        </main>

        <footer class="rm-footer">
            <div class="rm-footer__inner">
                <div>
                    <x-logo />
                    <p>{{ $appSettings['brand_tagline'] }}</p>
                </div>
                <nav aria-label="Footer links">
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('live') }}">Live TV</a>
                    <a href="{{ route('admin.login') }}">Admin</a>
                </nav>
                <p class="rm-footer__legal">{{ $appSettings['legal_notice'] }}</p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>

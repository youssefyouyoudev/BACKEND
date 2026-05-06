<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'RiFi Media TV' }}</title>
    <meta name="description" content="{{ $description ?? 'Browse and watch your imported IPTV playlists with a modern RiFi Media TV interface.' }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    {{-- HLS.js for native HLS playback in non-Safari browsers --}}
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js"></script>
</head>
<body class="app-body">
    @if (! empty($appSettings['maintenance_banner']))
        <div class="maintenance-banner">
            {{ $appSettings['maintenance_banner'] }}
        </div>
    @endif

    <div class="shell shell--public">
        <aside class="sidebar">
            <div class="sidebar__brand">
                <x-logo />
            </div>

            <nav class="sidebar__nav" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="sidebar__link {{ request()->routeIs('home') ? 'is-active' : '' }}">
                    <span class="sidebar__icon">●</span>
                    <span>Home</span>
                </a>
                <a href="{{ route('home', ['category' => request('category')]) }}#live-tv" class="sidebar__link">
                    <span class="sidebar__icon">▶</span>
                    <span>Live TV</span>
                </a>
                <a href="#categories" class="sidebar__link">
                    <span class="sidebar__icon">▣</span>
                    <span>Categories</span>
                </a>
                <a href="#library" class="sidebar__link">
                    <span class="sidebar__icon">♥</span>
                    <span>Library</span>
                </a>
                <a href="{{ route('admin.login') }}" class="sidebar__link">
                    <span class="sidebar__icon">⚙</span>
                    <span>Admin</span>
                </a>
            </nav>

            <div class="sidebar__footer">
                <p class="sidebar__eyebrow">Legal streaming manager</p>
                <p class="sidebar__copy">{{ $appSettings['brand_tagline'] }}</p>
            </div>
        </aside>

        <main class="main-content">
            <x-flash />
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        (() => {
            const stored = localStorage.getItem('rifi-theme');
            const theme = stored || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            document.documentElement.classList.add(`theme-${theme}`);
        })();
    </script>
    @php
        $seoTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES, 'UTF-8') ?: ($title ?? 'RifiMedia Sports - Football News, Live Scores, Fixtures & Match Updates');
        $seoDescription = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES, 'UTF-8') ?: ($description ?? 'Follow football news, live scores, fixtures, standings, match previews, and sports updates on RifiMedia Sports.');
        $seoRobots = trim($__env->yieldContent('robots')) ?: ($robots ?? 'index,follow');
        $seoCanonical = $canonical ?? url()->current();
        $seoImage = $image ?? asset('brand/rifi-logo.png');
        $baseSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'RifiMedia Sports',
                    'url' => url('/'),
                    'logo' => asset('brand/rifi-logo.png'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => 'RifiMedia Sports',
                    'description' => 'Sports news, live scores, fixtures, standings, and match updates.',
                    'publisher' => ['@id' => url('/').'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('search').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    @endphp
    <x-seo
        :title="$seoTitle"
        :description="$seoDescription"
        :canonical="$seoCanonical"
        :image="$seoImage"
        :robots="$seoRobots"
        :schema="$schema ?? $baseSchema"
    />
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

    <div class="rm-site site-shell" x-data="{ mobileNavOpen: false }">
        <header class="rm-navbar" aria-label="Primary navigation">
            <div class="rm-navbar__inner">
                <x-logo />

                <nav class="rm-navbar__links" aria-label="Main menu">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                    <a href="{{ route('scores') }}" class="{{ request()->routeIs('scores', 'live-scores') ? 'is-active' : '' }}">
                        Scores
                    </a>
                    <a href="{{ route('fixtures') }}" class="{{ request()->routeIs('fixtures') ? 'is-active' : '' }}">Fixtures</a>
                    <a href="{{ route('standings') }}" class="{{ request()->routeIs('standings') ? 'is-active' : '' }}">Standings</a>
                    <a href="{{ route('news.index') }}" class="{{ request()->routeIs('news.*') ? 'is-active' : '' }}">News</a>
                    <a href="{{ route('leagues.index') }}" class="{{ request()->routeIs('leagues.*') ? 'is-active' : '' }}">Leagues</a>
                    <a href="{{ route('teams.index') }}" class="{{ request()->routeIs('teams.*') ? 'is-active' : '' }}">Teams</a>
                    <a href="{{ route('home') }}#channels" class="{{ request()->routeIs('home') && request()->has('category') ? 'is-active' : '' }}">
                        Channels
                    </a>
                    <a href="{{ route('live') }}" class="{{ request()->routeIs('live', 'channels.show') ? 'is-active' : '' }}">
                        <span class="rm-live-dot" aria-hidden="true"></span>
                        Watch
                    </a>
                </nav>

                <div class="rm-navbar__actions">
                    <a href="{{ route('search') }}" class="rm-btn rm-btn-secondary rm-btn-sm">Search</a>
                    <button type="button" class="rm-theme-toggle" data-theme-toggle aria-label="Toggle light and dark mode">
                        <span data-theme-icon>Theme</span>
                    </button>
                    @auth
                        @if(auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rm-btn rm-btn-secondary rm-btn-sm">Admin</a>
                        @endif
                    @endauth
                    <a href="{{ route('scores') }}" class="rm-btn rm-btn-primary rm-btn-sm" aria-label="View live scores">Live Scores</a>
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
                <a href="{{ route('scores') }}">Scores</a>
                <a href="{{ route('fixtures') }}">Fixtures</a>
                <a href="{{ route('standings') }}">Standings</a>
                <a href="{{ route('news.index') }}">News</a>
                <a href="{{ route('leagues.index') }}">Leagues</a>
                <a href="{{ route('teams.index') }}">Teams</a>
                <a href="{{ route('home') }}#channels">Channels</a>
                <a href="{{ route('live') }}">Watch</a>
                @auth
                    @if(auth()->user()?->isAdmin())
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                @endauth
            </nav>
        </header>

        <main class="rm-main site-container">
            <x-flash />
            @yield('content')
        </main>

        <footer class="rm-footer" aria-label="Site footer">
            <div class="rm-footer__inner">
                <div class="rm-footer__brand">
                    <x-logo />
                    <p>Sports news, fixtures, scores, and match coverage — your clean sports media hub.</p>
                </div>
                <nav aria-label="Footer navigation" class="rm-footer__groups">
                    <span>
                        <strong>Coverage</strong>
                        <a href="{{ route('scores') }}">Live Scores</a>
                        <a href="{{ route('fixtures') }}">Fixtures</a>
                        <a href="{{ route('matches.index') }}">Matches</a>
                        <a href="{{ route('standings') }}">Standings</a>
                    </span>
                    <span>
                        <strong>Explore</strong>
                        <a href="{{ route('news.index') }}">News</a>
                        <a href="{{ route('leagues.index') }}">Leagues</a>
                        <a href="{{ route('teams.index') }}">Teams</a>
                        <a href="{{ route('live') }}">Channels</a>
                    </span>
                    <span>
                        <strong>Company</strong>
                        <a href="{{ route('about') }}">About</a>
                        <a href="{{ route('contact') }}">Contact</a>
                        <a href="{{ route('advertise') }}">Advertise</a>
                    </span>
                    <span>
                        <strong>Legal</strong>
                        <a href="{{ route('privacy') }}">Privacy</a>
                        <a href="{{ route('terms') }}">Terms</a>
                        <a href="{{ route('copyright') }}">Copyright</a>
                    </span>
                </nav>
                <p class="rm-footer__legal">&copy; {{ date('Y') }} RifiMedia Sports. Sports news, fixtures, scores, and match information. Users are responsible for ensuring they have rights to any submitted playlist or stream sources.</p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>

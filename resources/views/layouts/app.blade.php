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
            const storageKey = 'rifi-theme';
            const stored = localStorage.getItem(storageKey);
            const system = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            const theme = stored || system;
            if (! stored) localStorage.setItem(storageKey, theme);
            document.documentElement.classList.remove('theme-light', 'theme-dark', 'light', 'dark');
            document.documentElement.classList.add(`theme-${theme}`, theme);
            document.documentElement.dataset.theme = theme;
            document.documentElement.style.colorScheme = theme;
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
        $mainNav = [
            ['label' => 'Home', 'href' => route('home'), 'active' => request()->routeIs('home')],
            ['label' => 'Sports', 'href' => route('sports.index'), 'active' => request()->routeIs('sports.*', 'football.*', 'scores', 'fixtures', 'matches.*', 'leagues.*', 'teams.*', 'standings')],
            ['label' => 'Football', 'href' => route('sports.football'), 'active' => request()->routeIs('sports.football*', 'football.*', 'scores', 'live-scores')],
            ['label' => 'Live TV', 'href' => route('live-tv'), 'active' => request()->routeIs('live', 'live-tv', 'channels.show')],
            ['label' => 'Movies', 'href' => route('movies'), 'active' => request()->routeIs('movies')],
            ['label' => 'TV Shows', 'href' => route('tv-shows'), 'active' => request()->routeIs('tv-shows')],
            ['label' => 'Anime', 'href' => route('anime'), 'active' => request()->routeIs('anime')],
            ['label' => 'News', 'href' => route('news.index'), 'active' => request()->routeIs('news.*')],
        ];
        $mobileQuickNav = [
            ['label' => 'Home', 'href' => route('home'), 'icon' => 'H', 'active' => request()->routeIs('home')],
            ['label' => 'Sports', 'href' => route('sports.index'), 'icon' => 'S', 'active' => request()->routeIs('sports.*')],
            ['label' => 'Football', 'href' => route('sports.football'), 'icon' => 'F', 'active' => request()->routeIs('sports.football*', 'football.*', 'scores', 'live-scores')],
            ['label' => 'Live TV', 'href' => route('live-tv'), 'icon' => 'L', 'active' => request()->routeIs('live', 'live-tv', 'channels.show')],
            ['label' => 'News', 'href' => route('news.index'), 'icon' => 'N', 'active' => request()->routeIs('news.*')],
            ['label' => 'Search', 'href' => route('search'), 'icon' => 'Q', 'active' => request()->routeIs('search')],
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
        <header class="rm-navbar rm-premium-navbar" aria-label="Primary navigation" data-navbar>
            <div class="rm-navbar__inner">
                <x-logo />

                <nav class="rm-navbar__links" aria-label="Main menu">
                    @foreach($mainNav as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="rm-navbar__actions">
                    <a href="{{ route('search') }}" class="rm-icon-btn" aria-label="Search"><span aria-hidden="true">S</span></a>
                    <button type="button" class="rm-icon-btn rm-theme-toggle" data-theme-toggle aria-label="Switch theme" title="Switch theme">
                        <span data-theme-icon aria-hidden="true">D</span>
                    </button>
                    <a href="{{ route('live-tv') }}" class="rm-profile-btn rm-cta-btn">Watch Live</a>
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
                @foreach($mainNav as $item)
                    <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('standings') }}">Standings</a>
                <a href="{{ route('teams.index') }}">Teams</a>
                @auth
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                @else
                    <a href="{{ route('admin.login') }}">Login</a>
                @endauth
            </nav>
        </header>

        <main class="rm-main site-container">
            <x-flash />
            @yield('content')
        </main>

        <nav class="rm-bottom-nav" aria-label="Mobile quick navigation">
            @foreach($mobileQuickNav as $item)
                <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">
                    <span aria-hidden="true">{{ $item['icon'] }}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <button type="button" class="rm-floating-theme-toggle" data-theme-toggle aria-label="Switch theme" title="Switch theme">
            <span data-theme-icon aria-hidden="true">D</span>
        </button>

        <footer class="rm-footer rm-premium-footer" aria-label="Site footer">
            <div class="rm-footer__inner">
                <div class="rm-footer__brand">
                    <x-logo />
                    <p>RifiMedia Sports brings football live scores, fixtures, league coverage, verified news, and curated sports channels into one premium match-day destination.</p>
                </div>
                <nav aria-label="Footer navigation" class="rm-footer__groups">
                    <span>
                        <strong>Football</strong>
                        <a href="{{ route('sports.football') }}">Football Scores</a>
                        <a href="{{ route('fixtures') }}">Fixtures</a>
                        <a href="{{ route('leagues.index') }}">Leagues</a>
                        <a href="{{ route('news.index') }}">News</a>
                    </span>
                    <span>
                        <strong>Legal</strong>
                        <a href="{{ route('privacy') }}">Privacy</a>
                        <a href="{{ route('terms') }}">Terms</a>
                        <a href="{{ route('copyright') }}">Copyright</a>
                        <a href="{{ route('copyright') }}">DMCA</a>
                    </span>
                    <span>
                        <strong>Support</strong>
                        <a href="{{ route('contact') }}">Contact</a>
                        <a href="{{ route('advertise') }}">Advertise</a>
                        <a href="{{ route('contact') }}">Help Center</a>
                        <a href="{{ route('editorial-policy') }}">Editorial Policy</a>
                    </span>
                </nav>
                <div class="rm-footer__bottom">
                    <p>&copy; {{ date('Y') }} RifiMedia Sports. All rights reserved.</p>
                    <span class="rm-social-links" aria-label="Social links">
                        <a href="{{ route('news.index') }}">Newsroom</a>
                        <a href="{{ route('live-tv') }}">Live TV</a>
                        <a href="{{ route('contact') }}">Contact</a>
                    </span>
                </div>
                <p class="rm-footer__legal">RifiMedia Sports is a football media and discovery platform. Channel availability, scores, fixtures, and match information may change. Users are responsible for ensuring they have rights to submitted playlist or stream sources.</p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>

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
        $seoTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES, 'UTF-8') ?: ($title ?? 'RifiMedia - Live TV, Football Scores & Sports Streaming');
        $seoDescription = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES, 'UTF-8') ?: ($description ?? 'RifiMedia brings football scores, live TV channels, sports updates, and entertainment into one clean platform.');
        $seoRobots = trim($__env->yieldContent('robots')) ?: ($robots ?? 'index,follow');
        $seoCanonical = preg_replace('/^http:\/\//i', 'https://', $canonical ?? url()->current());
        $seoImage = $image ?? asset('brand/rifi-logo.png');
        $baseSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'RifiMedia',
                    'url' => url('/'),
                    'logo' => asset('brand/rifi-logo.png'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => 'RifiMedia',
                    'description' => 'Football scores, live TV channels, sports updates, and entertainment.',
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
            ['label' => 'Scores', 'icon' => 'scores', 'href' => route('sports.football'), 'active' => request()->routeIs('sports.football*', 'football.*', 'scores', 'live-scores', 'fixtures')],
            ['label' => 'Live TV', 'icon' => 'tv', 'href' => route('live-tv'), 'active' => request()->routeIs('live', 'live-tv')],
            ['label' => 'News', 'icon' => 'news', 'href' => route('news.index'), 'active' => request()->routeIs('news.*')],
            ['label' => 'Leagues', 'icon' => 'trophy', 'href' => route('leagues.index'), 'active' => request()->routeIs('leagues.*')],
            ['label' => 'Search', 'icon' => 'search', 'href' => route('search'), 'active' => request()->routeIs('search')],
        ];
        $mobileQuickNav = [
            ['label' => 'Home', 'href' => route('home'), 'icon' => 'home', 'active' => request()->routeIs('home')],
            ['label' => 'Scores', 'href' => route('sports.football'), 'icon' => 'scores', 'active' => request()->routeIs('sports.football*', 'football.*', 'scores', 'live-scores', 'fixtures')],
            ['label' => 'Live', 'href' => route('live-tv'), 'icon' => 'tv', 'active' => request()->routeIs('live', 'live-tv', 'channels.show')],
            ['label' => 'Leagues', 'href' => route('leagues.index'), 'icon' => 'trophy', 'active' => request()->routeIs('leagues.*')],
            ['label' => 'News', 'href' => route('news.index'), 'icon' => 'news', 'active' => request()->routeIs('news.*')],
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
        <header class="rm-navbar rm-premium-navbar" data-navbar>
            <div class="rm-navbar__inner">
                <x-logo />

                <nav class="rm-navbar__links" aria-label="Main menu">
                    @foreach($mainNav as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}"><x-icon :name="$item['icon']" />{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="rm-navbar__actions">
                    <a href="{{ route('search') }}" class="rm-icon-btn" aria-label="Search">
                        <x-icon name="search" />
                    </a>
                    <button type="button" class="rm-icon-btn rm-theme-toggle" data-theme-toggle aria-label="Switch theme" title="Switch theme">
                        <span class="rm-theme-icon rm-theme-icon--moon" aria-hidden="true"><x-icon name="moon" /></span>
                        <span class="rm-theme-icon rm-theme-icon--sun" aria-hidden="true"><x-icon name="sun" /></span>
                    </button>
                    @auth
                        @if(auth()->user()?->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="rm-profile-btn rm-cta-btn">Admin</a>
                        @endif
                    @else
                        <a href="{{ route('admin.login') }}" class="rm-profile-btn rm-cta-btn"><x-icon name="login" />Login</a>
                    @endauth
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
                        <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}"><x-icon :name="$item['icon']" />{{ $item['label'] }}</a>
                @endforeach
                @auth
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                @else
                    <a href="{{ route('admin.login') }}"><x-icon name="login" />Login</a>
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
                    <span aria-hidden="true"><x-icon :name="$item['icon']" /></span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <button type="button" class="rm-floating-theme-toggle" data-theme-toggle aria-label="Switch theme" title="Switch theme">
            <span class="rm-theme-icon rm-theme-icon--moon" aria-hidden="true"><x-icon name="moon" /></span>
            <span class="rm-theme-icon rm-theme-icon--sun" aria-hidden="true"><x-icon name="sun" /></span>
        </button>

        <footer class="rm-footer rm-premium-footer" aria-label="Site footer">
            <div class="rm-footer__inner">
                <div class="rm-footer__brand">
                    <x-logo />
                    <p>RifiMedia brings football scores, live TV channels, sports updates, and entertainment into one clean platform.</p>
                </div>
                <nav aria-label="Footer navigation" class="rm-footer__groups">
                    <span>
                        <strong>Football</strong>
                        <a href="{{ route('sports.football') }}">Football Scores</a>
                        <a href="{{ route('sports.football') }}">Fixtures</a>
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
                    <p>&copy; {{ date('Y') }} RifiMedia. All rights reserved.</p>
                    <span class="rm-social-links" aria-label="Social links">
                        <a href="{{ route('news.index') }}">Newsroom</a>
                        <a href="{{ route('live-tv') }}">Live TV</a>
                        <a href="{{ route('contact') }}">Contact</a>
                    </span>
                </div>
                <p class="rm-footer__legal">RifiMedia is a media discovery platform. Channel availability, scores, fixtures, and match information may change. Users are responsible for ensuring they have rights to submitted playlist or stream sources.</p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>

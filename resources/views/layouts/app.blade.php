<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Arabic:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @include('partials.theme-init')
    @php
        $seoTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES, 'UTF-8') ?: ($title ?? __('landing.meta.title'));
        $seoDescription = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES, 'UTF-8') ?: ($description ?? __('landing.meta.description'));
        $seoRobots = trim($__env->yieldContent('robots')) ?: ($robots ?? 'index,follow');
        $seoCanonical = preg_replace('/^http:\/\//i', 'https://', $canonical ?? url()->current());
        $seoImage = trim($__env->yieldContent('image')) ?: ($image ?? asset('brand/rifi-logo.png'));
        $baseSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'RiFiTV',
                    'url' => url('/'),
                    'logo' => asset('brand/rifi-logo.png'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => 'RiFiTV',
                    'description' => __('landing.meta.description'),
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
            ['label' => __('landing.nav.home'), 'icon' => 'home', 'href' => route('home'), 'active' => request()->routeIs('home')],
            ['label' => __('Football'), 'icon' => 'football', 'href' => route('football.index'), 'active' => request()->routeIs('sports.football*', 'football.*')],
            ['label' => __('Scores'), 'icon' => 'scores', 'href' => route('football.today'), 'active' => request()->routeIs('scores', 'live-scores')],
            ['label' => __('landing.nav.world_cup'), 'icon' => 'trophy', 'href' => route('world-cup-2026.index'), 'active' => request()->routeIs('world-cup*')],
            ['label' => __('TV Guide'), 'icon' => 'tv', 'href' => route('tv-guide.index'), 'active' => request()->routeIs('tv-guide.*')],
            ['label' => __('News'), 'icon' => 'news', 'href' => route('news.index'), 'active' => request()->routeIs('news.*')],
            ['label' => __('landing.nav.contact'), 'icon' => 'message', 'href' => route('contact'), 'active' => request()->routeIs('contact')],
        ];
        $mobileQuickNav = collect($mainNav)->only([0, 1, 2, 3, 4])->values()->all();
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
    <script>
        window.rifiLocale = @js(app()->getLocale());
        window.rifiTranslations = Object.assign({}, window.rifiTranslations || {}, @js([
            'Reconnecting...' => __('live.reconnecting'),
            'Saved' => __('common.saved'),
            'Favorite' => __('common.favorite'),
            'Group name' => __('live.group_name'),
            'Unknown group' => __('live.unknown_group'),
            'Channel' => __('live.channel'),
            'Channels' => __('live.channels'),
            'Watch now' => __('live.watch_now'),
            'Search' => __('common.search'),
            'Loading...' => __('common.loading'),
            'No results' => __('common.no_results'),
            'Stream link expired, refreshing...' => __('player.link_expired_refreshing'),
            'Unable to play this channel right now. Please try again later.' => __('player.unable_to_play'),
            'Movies' => __('categories.movies'),
            'Sports' => __('categories.sports'),
            'News' => __('categories.news'),
            'Kids' => __('categories.kids'),
            'Entertainment' => __('categories.entertainment'),
            'Documentary' => __('categories.documentary'),
            'Music' => __('categories.music'),
            'Religion' => __('categories.religion'),
            'General' => __('categories.general'),
            'Morocco' => __('countries.morocco'),
            'France' => __('countries.france'),
            'Spain' => __('countries.spain'),
            'Arabic' => __('languages.arabic'),
            'French' => __('languages.french'),
            'English' => __('languages.english'),
            'Spanish' => __('languages.spanish'),
        ]));
        window.rifiT = window.rifiT || function (key, fallbackOrReplacements = null) {
            if (key === null || key === undefined || key === '') {
                return typeof fallbackOrReplacements === 'string' ? fallbackOrReplacements : '';
            }

            const textKey = String(key).trim();
            const replacements = fallbackOrReplacements && typeof fallbackOrReplacements === 'object'
                ? fallbackOrReplacements
                : {};
            let value = window.rifiTranslations?.[textKey]
                || (typeof fallbackOrReplacements === 'string' ? fallbackOrReplacements : textKey);

            Object.entries(replacements).forEach(([name, replacement]) => {
                value = value.replaceAll(`:${name}`, String(replacement));
            });

            return value;
        };
        window.RifiAdsConfig = @js(\App\Models\AdSetting::publicConfig());
    </script>
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js', 'resources/js/ads.js'])
    @stack('styles')
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

                <nav class="rm-navbar__links" aria-label="{{ __("Main menu") }}">
                    @foreach($mainNav as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}"><x-icon :name="$item['icon']" />{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="rm-navbar__actions">
                    <a href="{{ route('search') }}" class="rm-icon-btn" aria-label="{{ __('landing.nav.search') }}">
                        <x-icon name="search" />
                    </a>
                    <div class="rtv-language-switcher" aria-label="{{ __('landing.nav.language') }}">
                        <a href="{{ route('language.switch', 'en') }}" class="{{ app()->isLocale('en') ? 'is-active' : '' }}" lang="en">EN</a>
                        <a href="{{ route('language.switch', 'ar') }}" class="{{ app()->isLocale('ar') ? 'is-active' : '' }}" lang="ar">AR</a>
                    </div>
                    <button type="button" class="rm-icon-btn rm-theme-toggle" data-theme-toggle aria-label="{{ __("Switch theme") }}" title="{{ __("Switch theme") }}">
                        <span class="rm-theme-icon rm-theme-icon--moon" aria-hidden="true"><x-icon name="moon" /></span>
                        <span class="rm-theme-icon rm-theme-icon--sun" aria-hidden="true"><x-icon name="sun" /></span>
                    </button>
                    @auth
                        @if(auth()->user()?->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="rm-profile-btn rm-cta-btn">{{ __('landing.nav.admin') }}</a>
                        @endif
                    @else
                        <a href="{{ route('admin.login') }}" class="rm-profile-btn rm-cta-btn"><x-icon name="login" />{{ __('landing.nav.login') }}</a>
                    @endauth
                    <a href="{{ route('football.today') }}" class="wc-nav-watch"><x-icon name="calendar" /> {{ __('Today') }}</a>
                    <button
                        type="button"
                        class="rm-mobile-nav"
                        aria-label="{{ __('landing.nav.open_menu') }}"
                        :aria-expanded="mobileNavOpen.toString()"
                        @click="mobileNavOpen = ! mobileNavOpen"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>

            <nav class="rm-navbar__drawer" x-show="mobileNavOpen" x-transition.opacity.origin.top @click.outside="mobileNavOpen = false" aria-label="{{ __("Mobile menu") }}">
                <div class="wc-nav-drawer__header">
                    <span class="wc-nav-live"><i></i> {{ __('Morocco time') }}</span>
                    <strong>{{ __('landing.world_cup.trophy_label') }}</strong>
                </div>
                @foreach($mainNav as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}"><x-icon :name="$item['icon']" />{{ $item['label'] }}</a>
                @endforeach
                <div class="rtv-language-switcher rtv-language-switcher--drawer" aria-label="{{ __('landing.nav.language') }}">
                    <a href="{{ route('language.switch', 'en') }}" class="{{ app()->isLocale('en') ? 'is-active' : '' }}" lang="en">{{ __("English") }}</a>
                    <a href="{{ route('language.switch', 'ar') }}" class="{{ app()->isLocale('ar') ? 'is-active' : '' }}" lang="ar">{{ __('landing.nav.arabic') }}</a>
                </div>
                <a href="{{ route('football.today') }}" class="wc-nav-drawer__watch"><x-icon name="calendar" /> {{ __('Today') }}</a>
                @auth
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">{{ __('landing.nav.admin') }}</a>
                    @endif
                @else
                    <a href="{{ route('admin.login') }}"><x-icon name="login" />{{ __('landing.nav.login') }}</a>
                @endauth
            </nav>
        </header>

        <main class="rm-main site-container">
            <x-flash />
            @yield('content')
        </main>

        <nav class="rm-bottom-nav" aria-label="{{ __("Mobile quick navigation") }}">
            @foreach($mobileQuickNav as $item)
                <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">
                    <span aria-hidden="true"><x-icon :name="$item['icon']" /></span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <button type="button" class="rm-floating-theme-toggle" data-theme-toggle aria-label="{{ __("Switch theme") }}" title="{{ __("Switch theme") }}">
            <span class="rm-theme-icon rm-theme-icon--moon" aria-hidden="true"><x-icon name="moon" /></span>
            <span class="rm-theme-icon rm-theme-icon--sun" aria-hidden="true"><x-icon name="sun" /></span>
        </button>

        <x-ad-slot name="sticky_mobile" type="sticky" label="{{ __('Sponsored') }}" />

        <footer class="rm-footer rm-premium-footer" aria-label="{{ __("Site footer") }}">
            <div class="rm-footer__inner">
                <div class="rm-footer__brand">
                    <x-logo />
                    <p>{{ __('landing.footer.description') }}</p>
                </div>
                <nav aria-label="{{ __("Footer navigation") }}" class="rm-footer__groups">
                    <span>
                        <strong>{{ __('landing.footer.football') }}</strong>
                        <a href="{{ route('football.today') }}">{{ __('landing.footer.scores') }}</a>
                        <a href="{{ route('football.schedules') }}">{{ __('landing.footer.fixtures') }}</a>
                        <a href="{{ route('world-cup-2026.index') }}">{{ __('landing.footer.world_cup') }}</a>
                        <a href="{{ route('tv-guide.index') }}">{{ __('TV Guide') }}</a>
                    </span>
                    <span>
                        <strong>{{ __('landing.footer.company') }}</strong>
                        <a href="{{ route('about') }}">{{ __('landing.footer.about') }}</a>
                        <a href="{{ route('contact') }}">{{ __('landing.footer.contact') }}</a>
                        <a href="{{ route('news.index') }}">{{ __('landing.footer.news') }}</a>
                    </span>
                    <span>
                        <strong>{{ __('landing.footer.legal') }}</strong>
                        <a href="{{ route('privacy') }}">{{ __('landing.footer.privacy') }}</a>
                        <a href="{{ route('terms') }}">{{ __('landing.footer.terms') }}</a>
                        <a href="{{ route('copyright') }}">{{ __('landing.footer.copyright') }}</a>
                    </span>
                </nav>
                <div class="rm-footer__bottom">
                    <p>&copy; {{ date('Y') }} RiFiTV. {{ __('landing.footer.rights') }}</p>
                    <span class="rm-social-links" aria-label="{{ __('landing.nav.language') }}">
                        <a href="{{ route('language.switch', 'en') }}" lang="en">{{ __("English") }}</a>
                        <a href="{{ route('language.switch', 'ar') }}" lang="ar">{{ __('landing.nav.arabic') }}</a>
                    </span>
                </div>
                <p class="rm-footer__legal">{{ __('landing.footer.notice') }}</p>
            </div>
        </footer>

    </div>

    @stack('scripts')
    <script>
        document.addEventListener('click', (event) => {
            const close = event.target.closest('[data-ad-dismiss]');
            if (!close) {
                return;
            }

            const slot = close.closest('[data-ad-slot]');
            if (slot) {
                sessionStorage.setItem(`rifitv_ad_closed_${slot.dataset.adSlot}`, '1');
                slot.remove();
            }
        });

        document.querySelectorAll('[data-ad-slot]').forEach((slot) => {
            if (sessionStorage.getItem(`rifitv_ad_closed_${slot.dataset.adSlot}`) === '1') {
                slot.remove();
            }
        });
    </script>
</body>
</html>

@php($heroMatch = $previewMatches->first())

<section class="rtv-landing-hero" aria-labelledby="rtv-hero-title">
    <div class="rtv-landing-hero__lights" aria-hidden="true"></div>
    <div class="rtv-landing-hero__copy" data-reveal>
        <span class="rtv-live-kicker"><i aria-hidden="true"></i>{{ __('landing.hero.eyebrow') }}</span>
        <h1 id="rtv-hero-title">{{ app()->isLocale('ar') ? 'كأس العالم 2026 قريب' : 'World Football 2026 Is Coming' }}</h1>
        <span class="sr-only">{{ __('landing.hero.title') }}</span>
        <p>{{ app()->isLocale('ar') ? 'شاهد المباريات، القنوات، المعلقين وتوقيت المغرب في مكان واحد' : __('landing.hero.subtitle') }}</p>
        <div class="rtv-button-row">
            <a class="rtv-button rtv-button--primary" href="#today-matches">
                <x-icon name="calendar" /> {{ __('landing.hero.matches_cta') }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup.index') }}">
                <x-icon name="trophy" /> {{ __('landing.hero.world_cup_cta') }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ config('ads.sponsor_url') }}" target="_blank" rel="nofollow sponsored noopener noreferrer">
                <x-icon name="play" /> {{ app()->isLocale('ar') ? 'سجّل دابا' : 'See premium offer' }}
            </a>
        </div>
        <div class="rtv-hero-trust" aria-label="{{ __('landing.hero.live') }}">
            <span><x-icon name="clock" />{{ __('landing.hero.morocco_time') }}</span>
            <span><x-icon name="shield" />{{ __('landing.hero.admin_links') }}</span>
            <span><x-icon name="mobile" />{{ __('landing.hero.responsive') }}</span>
        </div>
    </div>

    <div class="rtv-landing-hero__visual">
        <x-promo-banner priority />
    </div>
</section>

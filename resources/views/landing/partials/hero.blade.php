@php($heroMatch = $previewMatches->first())

<section class="rtv-landing-hero" aria-labelledby="rtv-hero-title">
    <div class="rtv-landing-hero__lights" aria-hidden="true"></div>
    <div class="rtv-landing-hero__copy" data-reveal>
        <span class="rtv-live-kicker"><i aria-hidden="true"></i>{{ __('landing.hero.eyebrow') }}</span>
        <h1 id="rtv-hero-title">{{ __('landing.hero.title') }}</h1>
        <p>{{ __('landing.hero.subtitle') }}</p>
        <div class="rtv-button-row">
            <a class="rtv-button rtv-button--primary" href="#today-matches">
                <x-icon name="calendar" /> {{ __('landing.hero.matches_cta') }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup.index') }}">
                <x-icon name="trophy" /> {{ __('landing.hero.world_cup_cta') }}
            </a>
        </div>
        <div class="rtv-hero-trust" aria-label="{{ __('landing.hero.live') }}">
            <span><x-icon name="clock" />{{ __('landing.hero.morocco_time') }}</span>
            <span><x-icon name="shield" />{{ __('landing.hero.admin_links') }}</span>
            <span><x-icon name="mobile" />{{ __('landing.hero.responsive') }}</span>
        </div>
    </div>

    <div class="rtv-landing-hero__visual" aria-hidden="true">
        <div class="rtv-orbit rtv-orbit--one"></div>
        <div class="rtv-orbit rtv-orbit--two"></div>
        <article class="rtv-floating-match rtv-floating-match--main">
            <header>
                <span>{{ $heroMatch?->group_name ?: __('landing.hero.floating_group') }}</span>
                <b><i></i>{{ $heroMatch ? __('landing.status.'.$heroMatch->broadcast_status) : __('landing.hero.live') }}</b>
            </header>
            <div class="rtv-floating-match__time">
                <strong>{{ $heroMatch?->morocco_kickoff_at?->format('H:i') ?: '20:00' }}</strong>
                <span>{{ __('landing.hero.morocco_time') }}</span>
            </div>
            <div class="rtv-floating-match__teams">
                <strong>
                    @if($heroMatch)
                        <x-team-flag :team="$heroMatch->home_team" :src="$heroMatch->home_flag" size="lg" loading="eager" />
                    @endif
                    <span>{{ $heroMatch?->home_team ?: 'RiFiTV' }}</span>
                </strong>
                <span>VS</span>
                <strong>
                    @if($heroMatch)
                        <x-team-flag :team="$heroMatch->away_team" :src="$heroMatch->away_flag" size="lg" loading="eager" />
                    @endif
                    <span>{{ $heroMatch?->away_team ?: 'Matchday' }}</span>
                </strong>
            </div>
            <footer>
                <span><x-icon name="tv" />{{ $heroMatch?->public_channel_name ?: __('landing.hero.floating_channel') }}</span>
            </footer>
        </article>
        <span class="rtv-float-chip rtv-float-chip--channel"><x-icon name="tv" />{{ __('landing.hero.floating_channel') }}</span>
        <span class="rtv-float-chip rtv-float-chip--commentator"><x-icon name="user" />{{ __('landing.hero.floating_commentator') }}</span>
        <span class="rtv-float-chip rtv-float-chip--status"><x-icon name="signal" />{{ __('landing.hero.floating_status') }}</span>
    </div>
</section>

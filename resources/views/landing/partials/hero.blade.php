<section class="rtv-landing-hero" aria-labelledby="rtv-hero-title">
    <div class="rtv-landing-hero__copy" data-reveal>
        <span class="rtv-live-kicker"><i aria-hidden="true"></i>{{ __('Football in Morocco time') }}</span>
        <h1 id="rtv-hero-title">RiFiTV</h1>
        <p>{{ __('Football scores, schedules, news, and World Cup 2026 guide in Morocco time.') }}</p>
        <div class="rtv-button-row">
            <a class="rtv-button rtv-button--primary" href="{{ route('football.today') }}">
                <x-icon name="calendar" /> {{ __("Today's Matches") }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup-2026.schedule') }}">
                <x-icon name="trophy" /> {{ __('World Cup Schedule') }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ route('news.index') }}">
                <x-icon name="news" /> {{ __('Latest News') }}
            </a>
        </div>
        <div class="rtv-hero-trust">
            <span><x-icon name="clock" />{{ __('Africa/Casablanca') }}</span>
            <span><x-icon name="scores" />{{ __('Scores and results') }}</span>
            <span><x-icon name="tv" />{{ __('TV guide information') }}</span>
        </div>
    </div>

    <div class="rtv-landing-hero__visual">
        <x-promo-banner priority />
    </div>
</section>

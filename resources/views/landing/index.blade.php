@extends('layouts.app')

@section('title', __('landing.meta.title'))
@section('description', __('landing.meta.description'))
@section('image', asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<div class="rtv-landing">
    @include('landing.partials.hero')
    <x-ad-slot name="home_after_hero" type="banner" />
    @include('landing.partials.matches')
    @if($nextKnockoutMatches->isNotEmpty())
        <section class="rtv-landing-section" aria-labelledby="rtv-road-final-title">
            <div class="rtv-section-heading">
                <div>
                    <span class="rtv-kicker">{{ __('World Cup 2026') }}</span>
                    <h2 id="rtv-road-final-title">{{ __('Road to Final') }}</h2>
                    <p>{{ __('Next knockout matches from Round of 32 to Final in Morocco time.') }}</p>
                </div>
                <a class="rtv-text-link" href="{{ route('world-cup-2026.knockout') }}">{{ __('View full knockout schedule') }} <x-icon name="arrow-up-right" /></a>
            </div>
            <x-road-to-final :matches-by-stage="$nextKnockoutMatches->groupBy('stage')" compact />
        </section>
    @endif
    <x-ad-slot name="home_between_matches_world_cup" type="inline" compact />
    <section class="rtv-landing-section" aria-labelledby="morocco-focus-title">
        <div class="rtv-section-heading">
            <div>
                <span class="rtv-kicker">{{ __('Morocco focus') }}</span>
                <h2 id="morocco-focus-title">{{ __('Morocco at World Cup 2026') }}</h2>
                <p>{{ __('Find Morocco fixtures, group information, kickoff times, and match details in one focused page.') }}</p>
            </div>
            <a class="rtv-text-link" href="{{ route('world-cup-2026.morocco') }}">{{ __('Morocco fixtures') }} <x-icon name="arrow-up-right" /></a>
        </div>
    </section>
    @include('landing.partials.world-cup')
    <section class="rtv-landing-section">
        <div class="rtv-section-heading">
            <div>
                <span class="rtv-kicker">{{ __('TV guide') }}</span>
                <h2>{{ __('Football listings in Morocco time') }}</h2>
                <p>{{ __('Match schedules, competitions, broadcaster information, commentators, and status updates.') }}</p>
            </div>
            <a class="rtv-text-link" href="{{ route('tv-guide.index') }}">{{ __('Open TV guide') }} <x-icon name="arrow-up-right" /></a>
        </div>
    </section>
    @include('landing.partials.channels')
    @include('landing.partials.features')
    <section class="rtv-landing-section">
        <div class="rtv-section-heading">
            <div>
                <span class="rtv-kicker">{{ __('Newsroom') }}</span>
                <h2>{{ __('Latest football news') }}</h2>
                <p>{{ __('World Cup 2026, Morocco, Africa, Europe, transfers, previews, and results.') }}</p>
            </div>
            <a class="rtv-text-link" href="{{ route('news.index') }}">{{ __('Read football news') }} <x-icon name="arrow-up-right" /></a>
        </div>
    </section>
    @include('landing.partials.faq')

    <section class="rtv-final-cta" data-reveal aria-labelledby="rtv-final-title">
        <x-promo-banner compact />
        <span class="rtv-kicker">{{ __('landing.cta.eyebrow') }}</span>
        <h2 id="rtv-final-title">{{ __('landing.cta.title') }}</h2>
        <p>{{ __('landing.cta.copy') }}</p>
        <div class="rtv-button-row">
            <a class="rtv-button rtv-button--primary" href="{{ route('football.today') }}">
                <x-icon name="scores" /> {{ __('landing.cta.matches') }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup-2026.schedule') }}">
                <x-icon name="trophy" /> {{ __('landing.cta.world_cup') }}
            </a>
        </div>
    </section>
    <x-ad-slot name="home_before_footer" type="banner" compact />
</div>
@endsection

@extends('layouts.app')

@section('title', __('landing.meta.title'))
@section('description', __('landing.meta.description'))
@section('image', asset('assets/images/fifa_world_cup_2026_tease.png'))

@section('content')
<div class="rtv-landing">
    @include('landing.partials.hero')
    @include('landing.partials.matches')
    @include('landing.partials.world-cup')
    @include('landing.partials.channels')
    @include('landing.partials.features')
    @include('landing.partials.faq')

    <section class="rtv-final-cta" data-reveal aria-labelledby="rtv-final-title">
        <span class="rtv-kicker">{{ __('landing.cta.eyebrow') }}</span>
        <h2 id="rtv-final-title">{{ __('landing.cta.title') }}</h2>
        <p>{{ __('landing.cta.copy') }}</p>
        <div class="rtv-button-row">
            <a class="rtv-button rtv-button--primary" href="{{ route('sports.football') }}">
                <x-icon name="scores" /> {{ __('landing.cta.matches') }}
            </a>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup.index') }}">
                <x-icon name="trophy" /> {{ __('landing.cta.world_cup') }}
            </a>
        </div>
    </section>
</div>
@endsection

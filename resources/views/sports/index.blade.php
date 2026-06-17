@extends('layouts.app')

@section('title', __("Sports & Football | RifiMedia"))
@section('description', __("Follow real football scores, fixtures, results, and available TV channels on RifiMedia."))

@section('content')
<div class="rm-page rm-media-platform-page">
    <x-page-hero
        eyebrow="{{ __("Sports") }}"
        title="{{ __("Football scores, fixtures, and channels") }}"
        description="{{ __("Football scores, fixtures, results, schedules, and TV guide information in Morocco time on RiFiTV.") }}"
    >
        <div class="rm-hero-actions">
            <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-primary">{{ __("Open Football") }}</a>
            <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-secondary">{{ __("Watch Live TV") }}</a>
        </div>
    </x-page-hero>

    <section class="rm-section">
        <x-section-header eyebrow="{{ __("Today") }}" title="{{ __("Football matches") }}" href="{{ route('sports.football') }}" action="{{ __("All football") }}" />
        @if($matches->count())
            <div class="football-match-grid">
                @foreach($matches as $match)
                    <x-match-card :match="$match" />
                @endforeach
            </div>
        @else
            <x-empty-state title="{{ __("No matches available") }}" message="{{ __("Football match data will appear here as soon as TheSportsDB has fixtures for the configured leagues.") }}" />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="{{ __("Leagues") }}" title="{{ __("Configured competitions") }}" />
        <div class="rm-media-grid">
            @foreach($leagues as $league)
                <x-media-card
                    :title="$league['name']"
                    :description="$league['country']"
                    :href="route('sports.football')"
                    label="{{ __("Football") }}"
                />
            @endforeach
        </div>
    </section>
</div>
@endsection

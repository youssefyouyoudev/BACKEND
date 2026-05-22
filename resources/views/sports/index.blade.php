@extends('layouts.app')

@section('title', 'Sports & Football | RifiMedia')
@section('description', 'Follow real football scores, fixtures, results, and available TV channels on RifiMedia.')

@section('content')
<div class="rm-page rm-media-platform-page">
    <x-page-hero
        eyebrow="Sports"
        title="Football scores, fixtures, and channels"
        description="Sports starts with football on RifiMedia: real match data, clean cards, and direct watch links when channels exist in the live TV playlist."
    >
        <div class="rm-hero-actions">
            <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-primary">Open Football</a>
            <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-secondary">Watch Live TV</a>
        </div>
    </x-page-hero>

    <section class="rm-section">
        <x-section-header eyebrow="Today" title="Football matches" href="{{ route('sports.football') }}" action="All football" />
        @if($matches->count())
            <div class="football-match-grid">
                @foreach($matches as $match)
                    <x-match-card :match="$match" />
                @endforeach
            </div>
        @else
            <x-empty-state title="No matches available" message="Football match data will appear here as soon as TheSportsDB has fixtures for the configured leagues." />
        @endif
    </section>

    <section class="rm-section">
        <x-section-header eyebrow="Leagues" title="Configured competitions" />
        <div class="rm-media-grid">
            @foreach($leagues as $league)
                <x-media-card
                    :title="$league['name']"
                    :description="$league['country']"
                    :href="route('sports.football')"
                    label="Football"
                />
            @endforeach
        </div>
    </section>
</div>
@endsection

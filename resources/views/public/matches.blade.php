@extends('layouts.app')

@section('title', __("Football Match Center, Previews & Reports | RifiMedia"))
@section('description', __("Browse football match center pages for previews, reports, stats, timelines, lineups, and related sports coverage."))

@section('content')
<div class="rm-page rm-page--matches">
    <section class="rm-page-hero">
        <span class="rm-kicker">{{ __("Match center") }}</span>
        <h1>{{ __("Football Match Center") }}</h1>
        <p>{{ __("Match pages are ready for previews, reports, scoreboards, stats, lineups, timelines, and related articles.") }}</p>
    </section>

    <x-ad-slot name="matches_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div>
            @if($matches->isEmpty())
                <div class="rm-empty-state">
                    <span>{{ __("No matches loaded") }}</span>
                    <strong>{{ __("Match center cards will appear here when reliable fixture data is connected.") }}</strong>
                    <p>{{ __("This avoids presenting invented scorelines or fake live match information.") }}</p>
                </div>
            @endif
        </div>
        <aside class="rm-side-rail">
            <x-ad-slot name="matches_sidebar_rectangle" size="rectangle" />
            <div class="rm-topic-card">
                <h2>{{ __("Browse competitions") }}</h2>
                @foreach($leagues as $league)
                    <a href="{{ route('leagues.show', $league['slug']) }}">{{ $league['name'] }}</a>
                @endforeach
            </div>
        </aside>
    </section>
</div>
@endsection

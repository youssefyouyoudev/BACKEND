@extends('layouts.app')

@section('title', 'Live Football Scores Today | RifiMedia')
@section('description', 'Follow today’s football live scores, fixtures, results, and match updates on RifiMedia.')

@section('content')
<div class="rm-page rm-page--scores">
    <section class="rm-page-hero">
        <span class="rm-kicker">Live scores</span>
        <h1>Live Football Scores Today</h1>
        <p>Follow match status, minute-by-minute updates, fixtures, and final results when verified score data is connected.</p>
        <div class="rm-filter-tabs" role="tablist" aria-label="Score dates">
            <a href="{{ route('scores', ['date' => 'yesterday']) }}">Yesterday</a>
            <a href="{{ route('scores') }}" class="is-active">Today</a>
            <a href="{{ route('scores', ['date' => 'tomorrow']) }}">Tomorrow</a>
        </div>
    </section>

    <x-ad-slot name="scores_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div>
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Match feed</p>
                    <h2>Scores and results</h2>
                </div>
            </div>

            @if($matches->isEmpty())
                <div class="rm-empty-state">
                    <span>No live scores available</span>
                    <strong>Live scores will appear here when matches are available.</strong>
                    <p>Try another date or open the football dashboard for upcoming fixtures.</p>
                </div>
            @endif
        </div>

        <aside class="rm-side-rail">
            <x-ad-slot name="scores_sidebar_rectangle" size="rectangle" />
            <div class="rm-topic-card">
                <h2>League filters</h2>
                <div class="rm-topic-cloud">
                    @foreach($leagues as $league)
                        <a href="{{ route('leagues.show', $league['slug']) }}">{{ $league['name'] }}</a>
                    @endforeach
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection

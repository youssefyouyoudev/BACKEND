@extends('layouts.app')

@section('title', 'Football Standings & League Tables | RifiMedia')
@section('description', 'Follow football standings, league tables, team form, points, wins, draws, losses, and competition updates on RifiMedia.')

@section('content')
<div class="rm-page rm-page--standings">
    <section class="rm-page-hero">
        <span class="rm-kicker">Standings</span>
        <h1>Football Standings & League Tables</h1>
        <p>Follow competition tables, team form, points, and league movement in a responsive sports-data layout.</p>
    </section>

    <x-ad-slot name="standings_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div class="rm-standings-table">
            <div class="rm-empty-state">
                <span>No standings available right now</span>
                <strong>League tables will appear here as soon as reliable standings data is available.</strong>
            </div>
        </div>
        <aside class="rm-side-rail">
            <x-ad-slot name="standings_sidebar_rectangle" size="rectangle" />
            <div class="rm-topic-card">
                <h2>League pages</h2>
                @foreach($leagues as $league)
                    <a href="{{ route('leagues.show', $league['slug']) }}">{{ $league['name'] }}</a>
                @endforeach
            </div>
        </aside>
    </section>
</div>
@endsection

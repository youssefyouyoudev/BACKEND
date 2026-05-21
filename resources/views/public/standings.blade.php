@extends('layouts.app')

@section('title', 'Football Standings & League Tables | RifiMedia Sports')
@section('description', 'Follow football standings, league tables, team form, points, wins, draws, losses, and competition updates on RifiMedia Sports.')

@section('content')
<div class="rm-page rm-page--standings">
    <section class="rm-page-hero">
        <span class="rm-kicker">Standings</span>
        <h1>Football Standings & League Tables</h1>
        <p>League table layouts are prepared for verified standings data across domestic and international competitions.</p>
    </section>

    <x-ad-slot name="standings_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div class="rm-standings-table">
            <div class="rm-empty-state">
                <span>Standings ready</span>
                <strong>Tables will appear here when a reliable standings feed is connected.</strong>
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

@extends('layouts.app')

@section('title', 'Football Fixtures, Match Calendar & Kickoff Times | RifiMedia')
@section('description', 'Browse football fixtures, upcoming matches, league schedules, kickoff times, and match center links on RifiMedia.')

@section('content')
<div class="rm-page rm-page--fixtures">
    <section class="rm-page-hero">
        <span class="rm-kicker">Fixtures</span>
        <h1>Football Fixtures & Match Calendar</h1>
        <p>Calendar-ready layouts for upcoming matches, league filters, kickoff times, and match center previews.</p>
    </section>

    <x-ad-slot name="fixtures_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div>
            <div class="rm-fixture-calendar">
                @foreach(['Yesterday', 'Today', 'Tomorrow', 'This weekend'] as $day)
                    <button type="button" class="{{ $loop->iteration === 2 ? 'is-active' : '' }}">{{ $day }}</button>
                @endforeach
            </div>

            @if($fixtures->isEmpty())
                <div class="rm-empty-state">
                    <span>No fixtures loaded</span>
                    <strong>Upcoming matches will appear here when a verified fixture feed is connected.</strong>
                </div>
            @endif
        </div>

        <aside class="rm-side-rail">
            <x-ad-slot name="fixtures_sidebar_rectangle" size="rectangle" />
            <div class="rm-topic-card">
                <h2>Competitions</h2>
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

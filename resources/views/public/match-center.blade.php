@extends('layouts.app')

@php
    $pageName = $item['name'];
@endphp

@section('title', $pageName.' Match Center | RifiMedia Sports')
@section('description', $item['description'] ?? 'Match center coverage with previews, fixtures, stats, standings, and related sports updates.')

@section('content')
<div class="rm-page rm-page--match-center">
    <section class="rm-match-center-hero">
        <span class="rm-kicker">{{ ucfirst($mode) }} center</span>
        <h1>{{ $pageName }}</h1>
        <p>{{ $item['description'] ?? 'Coverage center ready for reliable sports data.' }}</p>
        <div class="rm-match-center-score">
            <div><span>Home</span><strong>-</strong></div>
            <div><span>Status</span><strong>Preview</strong></div>
            <div><span>Away</span><strong>-</strong></div>
        </div>
    </section>

    <x-ad-slot name="match_center_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div class="rm-match-tabs">
            @foreach(['Preview', 'Timeline', 'Stats', 'Lineups', 'News'] as $tab)
                <section>
                    <h2>{{ $tab }}</h2>
                    <p>{{ $tab }} content will appear here when verified match, team, and editorial data is connected.</p>
                </section>
            @endforeach
        </div>
        <aside class="rm-side-rail">
            <x-ad-slot name="match_center_sidebar" size="rectangle" />
            @if($relatedChannels->count())
                <div class="rm-topic-card">
                    <h2>Related media channels</h2>
                    @foreach($relatedChannels as $channel)
                        <a href="{{ route('channels.show', $channel) }}">{{ $channel->clean_display_name }}</a>
                    @endforeach
                </div>
            @endif
        </aside>
    </section>
</div>
@endsection

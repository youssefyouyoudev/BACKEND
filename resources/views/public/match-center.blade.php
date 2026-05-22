@extends('layouts.app')

@php
    $pageName = $item['name'];
    $tvChannels = collect($tvChannels ?? []);
    $availableTvChannels = $tvChannels->where('available', true);
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

    <section class="rm-section rm-match-tv" aria-labelledby="rm-match-tv-title">
        <div class="rm-section__header">
            <span class="rm-kicker">Broadcasts</span>
            <h2 id="rm-match-tv-title">Where to watch</h2>
        </div>

        @if($tvChannels->isEmpty())
            <p class="rm-match-tv__empty">Broadcast information is not available for this match.</p>
        @else
            @if($availableTvChannels->isEmpty())
                <p class="rm-match-tv__empty">Channels found, but not available in our playlist yet.</p>
            @endif

            <div class="rm-match-tv__grid">
                @foreach($tvChannels as $channel)
                    @if($channel['available'])
                        <a href="{{ $channel['watch_url'] }}" class="rm-match-tv-card rm-match-tv-card--available">
                            <span class="rm-match-tv-card__logo">
                                @if($channel['logo'])
                                    <img src="{{ $channel['logo'] }}" alt="" loading="lazy">
                                @else
                                    <span>{{ mb_substr($channel['matched_channel_name'] ?? $channel['channel'], 0, 1) }}</span>
                                @endif
                            </span>
                            <span class="rm-match-tv-card__body">
                                <strong>{{ $channel['matched_channel_name'] ?? $channel['channel'] }}</strong>
                                @if($channel['country'])
                                    <small>{{ $channel['country'] }}</small>
                                @endif
                            </span>
                            <span class="rm-match-tv-card__badge">Watch</span>
                        </a>
                    @else
                        <div class="rm-match-tv-card rm-match-tv-card--unavailable" aria-disabled="true">
                            <span class="rm-match-tv-card__logo rm-match-tv-card__logo--fallback">
                                <span>{{ mb_substr($channel['channel'], 0, 1) }}</span>
                            </span>
                            <span class="rm-match-tv-card__body">
                                <strong>{{ $channel['channel'] }}</strong>
                                @if($channel['country'])
                                    <small>{{ $channel['country'] }}</small>
                                @endif
                                <em>Not available in playlist</em>
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </section>

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

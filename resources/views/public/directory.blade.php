@extends('layouts.app')

@section('title', $title.' | RifiMedia')
@section('description', $description)

@section('content')
<div class="rm-page rm-page--directory">
    <section class="rm-page-hero">
        <span class="rm-kicker">{{ ucfirst($kind) }}</span>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </section>

    <x-ad-slot :name="$kind.'_leaderboard'" size="leaderboard" />

    <section class="rm-section">
        @if($items->isEmpty())
            <x-empty-state title="No {{ $kind }} available" message="Browse football scores and live TV while this section is updated." action="Football Scores" :href="route('sports.football')" />
        @else
            <div class="rm-directory-grid">
                @foreach($items as $item)
                    <article class="rm-directory-card">
                        <span>{{ $item['region'] }}</span>
                        <h2>{{ $item['name'] }}</h2>
                        <p>Fixtures, standings, news, match previews, and related coverage in one place.</p>
                        <a href="{{ $kind === 'leagues' ? route('leagues.show', $item['slug']) : route('teams.show', $item['slug']) }}">Open page</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

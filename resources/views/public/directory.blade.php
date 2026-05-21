@extends('layouts.app')

@section('title', $title.' | RifiMedia Sports')
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
        <div class="rm-directory-grid">
            @foreach($items as $item)
                <article class="rm-directory-card">
                    <span>{{ $item['region'] }}</span>
                    <h2>{{ $item['name'] }}</h2>
                    <p>Fixtures, standings, news, match previews, and related coverage area.</p>
                    <a href="{{ $kind === 'leagues' ? route('leagues.show', $item['slug']) : route('teams.show', $item['slug']) }}">Open page</a>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection

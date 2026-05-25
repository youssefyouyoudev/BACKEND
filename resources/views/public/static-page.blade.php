@extends('layouts.app')

@section('title', $page['title'].' | RifiMedia')
@section('description', $page['description'])

@section('content')
<div class="rm-page rm-page--static">
    <section class="rm-page-hero">
        <span class="rm-kicker">RifiMedia</span>
        <h1>{{ $page['title'] }}</h1>
        <p>{{ $page['description'] }}</p>
    </section>
    <section class="rm-section rm-readable-card">
        <p>{{ $page['body'] }}</p>
        @if($slug === 'advertise')
            <x-ad-slot name="advertise_demo_leaderboard" size="leaderboard" />
        @endif
    </section>
</div>
@endsection

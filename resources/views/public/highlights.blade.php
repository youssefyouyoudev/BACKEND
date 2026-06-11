@extends('layouts.app')

@section('title', __("Football Highlights Information | RifiMedia"))
@section('description', __("Discover football highlights information, match recaps, and related sports coverage on RifiMedia."))

@section('content')
<div class="rm-page">
    <section class="rm-page-hero">
        <span class="rm-kicker">{{ __("Highlights") }}</span>
        <h1>{{ __("Highlights & Match Recap Information") }}</h1>
        <p>{{ __("This page is prepared for legal highlight information, recap articles, and video metadata. No copyrighted highlights are embedded unless rights are confirmed.") }}</p>
    </section>
    <x-ad-slot name="highlights_leaderboard" size="leaderboard" />
    <section class="rm-section">
        <div class="rm-topic-cloud">
            @foreach($topics as $topic)
                <a href="{{ route('search', ['q' => $topic]) }}">{{ $topic }}</a>
            @endforeach
        </div>
    </section>
</div>
@endsection

@extends('layouts.app')

@section('title', $page['title'].' | RifiMedia')
@section('description', $page['description'])

@section('content')
<div class="rm-page rm-page--static">
    <section class="rm-page-hero">
        <span class="rm-kicker">{{ __("RifiMedia") }}</span>
        <h1>{{ $page['title'] }}</h1>
        <p>{{ $page['description'] }}</p>
    </section>
    <section class="rm-section rm-readable-card">
        <p>{{ $page['body'] }}</p>
    </section>
    <x-ad-slot :name="$slug.'_bottom'" type="inline" compact />
</div>
@endsection

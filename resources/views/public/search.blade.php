@extends('layouts.app')

@section('title', 'Search Sports News, Scores & Channels | RifiMedia Sports')
@section('description', 'Search RifiMedia Sports for football topics, fixtures, league pages, team pages, and permitted channel information.')
@section('robots', 'noindex,follow')

@section('content')
<div class="rm-page">
    <section class="rm-page-hero">
        <span class="rm-kicker">Search</span>
        <h1>Search RifiMedia Sports</h1>
        <form class="rm-search rm-search--wide" action="{{ route('search') }}" method="GET">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search teams, leagues, news, fixtures">
            <button class="rm-btn rm-btn-primary" type="submit">Search</button>
        </form>
    </section>
    <section class="rm-section">
        <div class="rm-empty-state">
            <span>Search ready</span>
            <strong>Connect article, fixture, team, and league indexes to return full search results.</strong>
        </div>
        @if($channels->count())
            <div class="rm-match-grid">
                @foreach($channels as $channel)
                    <x-channel-card :channel="[
                        'id' => $channel->id,
                        'name' => $channel->clean_display_name,
                        'original_name' => $channel->name,
                        'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
                        'category' => $channel->group_title ?: 'Sports',
                        'program' => ['title' => 'Channel information'],
                        'watch_url' => route('channels.show', $channel),
                        'display_tags' => $channel->display_tags,
                        'quality_label' => $channel->quality_label,
                    ]" />
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

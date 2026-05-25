@extends('layouts.app')

@section('title', 'Sports News & Football Updates | RifiMedia')
@section('description', 'Read sports news, football updates, match previews, reports, and trending coverage from RifiMedia.')

@section('content')
<div class="rm-page rm-page--editorial">
    <section class="rm-page-hero">
        <span class="rm-kicker">RifiMedia News</span>
        <h1>Sports news and football updates</h1>
        <p>Match previews, reports, transfer stories, league coverage, and team news in a clean reading experience.</p>
    </section>

    <x-ad-slot name="news_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div>
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">Latest stories</p>
                    <h2>Published articles</h2>
                </div>
            </div>

            @if($articles->isEmpty())
                <div class="rm-empty-state">
                    <span>News articles will appear here soon</span>
                    <strong>Check back later for football stories, previews, and sports updates.</strong>
                </div>
            @else
                <div class="rm-news-grid">
                    @foreach($articles as $article)
                        <article class="rm-story-card">
                            <img src="{{ $article->featured_image ?: config('rifimedia_visuals.images.fallback_sports') }}" alt="{{ $article->title }}" loading="lazy" data-fallback-src="{{ config('rifimedia_visuals.images.fallback_sports') }}">
                            <span class="rm-story-card__label">{{ $article->category?->name ?? 'Football' }}</span>
                            <h3>{{ $article->title }}</h3>
                            <p>{{ $article->excerpt }}</p>
                            <small>{{ $article->published_at?->format('M j, Y') }} | {{ $article->author?->name ?? 'RifiMedia Desk' }}</small>
                            <a href="{{ route('news.show', $article->slug) }}">Read article</a>
                        </article>
                        @if($loop->iteration === 6)
                            <x-ad-slot name="news_in_feed" size="in-feed" />
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="rm-side-rail">
            <x-ad-slot name="news_sidebar_rectangle" size="rectangle" />
            <div class="rm-topic-card">
                <h2>Trending topics</h2>
                <div class="rm-topic-cloud">
                    @foreach($topics as $topic)
                        <a href="{{ route('search', ['q' => $topic]) }}">{{ $topic }}</a>
                    @endforeach
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection

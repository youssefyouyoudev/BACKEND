@extends('layouts.app')

@section('title', 'Sports News & Football Updates | RifiMedia Sports')
@section('description', 'Read sports news, football updates, match previews, reports, and trending coverage from RifiMedia Sports.')

@section('content')
<div class="rm-page rm-page--editorial">
    <section class="rm-page-hero">
        <span class="rm-kicker">Newsroom</span>
        <h1>Sports News & Football Updates</h1>
        <p>Editorial pages are ready for match previews, reports, transfer stories, league coverage, and team news.</p>
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
                    <span>No published news yet</span>
                    <strong>Articles will appear here after an editorial publishing workflow is connected.</strong>
                    <p>Use this section for original sports articles only. Avoid scraped stories, misleading headlines, or unauthorized streaming language.</p>
                </div>
            @else
                <div class="rm-news-grid">
                    @foreach($articles as $article)
                        <article class="rm-story-card">
                            <span class="rm-story-card__label">{{ $article->category?->name ?? 'Football' }}</span>
                            <h3>{{ $article->title }}</h3>
                            <p>{{ $article->excerpt }}</p>
                            <small>{{ $article->published_at?->format('M j, Y') }} · {{ $article->author?->name ?? 'RifiMedia Sports Desk' }}</small>
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

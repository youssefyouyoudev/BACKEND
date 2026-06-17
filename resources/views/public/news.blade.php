@extends('layouts.app')

@section('title', __("Football News, Morocco and World Cup 2026 | RiFiTV"))
@section('description', __("Read RiFiTV football news, Morocco updates, World Cup 2026 coverage, match previews, results, transfers, and TV guide news."))

@php
    $featuredArticle = $articles->first();
    $remainingArticles = $articles->skip(1);
@endphp

@section('content')
<div class="rm-page rm-page--editorial">
    <section class="rm-page-hero rm-news-hero" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <span class="rm-kicker"><x-icon name="news" /> {{ __("RiFiTV News") }}</span>
        <h1>{{ __("Sports news and football updates") }}</h1>
        <p>{{ __("Match previews, results, Morocco coverage, World Cup 2026 updates, and football news from the RiFiTV desk.") }}</p>
    </section>

    <x-ad-slot name="news_leaderboard" size="leaderboard" />

    @if($articles->isEmpty())
        <section class="rm-news-empty" aria-labelledby="rm-news-empty-title">
            <span class="rm-news-empty__icon"><x-icon name="news" /></span>
            <p class="rm-eyebrow">{{ __("Newsroom") }}</p>
            <h2 id="rm-news-empty-title">{{ __("Newsroom is being prepared") }}</h2>
            <p>{{ __("Published football stories will appear here when verified editorial content is ready.") }}</p>
            <div class="rm-hero-actions">
                <a href="{{ route('football.today') }}" class="rm-btn rm-btn-primary"><x-icon name="scores" />{{ __("View Football Scores") }}</a>
                <a href="{{ route('tv-guide.index') }}" class="rm-btn rm-btn-secondary"><x-icon name="tv" />{{ __("Open TV Guide") }}</a>
            </div>
        </section>
    @else
        <section class="rm-section rm-news-featured" aria-labelledby="rm-news-featured-title">
            <div class="rm-section-header">
                <div>
                    <p class="rm-eyebrow">{{ __("Featured story") }}</p>
                    <h2 id="rm-news-featured-title">{{ __("Latest sports update") }}</h2>
                </div>
            </div>

            <article class="rm-news-featured-card">
                <a href="{{ route('news.show', $featuredArticle->slug) }}" class="rm-news-featured-card__media" aria-label="Read {{ $featuredArticle->title }}">
                    <img src="{{ $featuredArticle->featured_image ?: config('rifimedia_visuals.images.fallback_sports') }}" alt="{{ $featuredArticle->title }}" loading="eager" data-fallback-src="{{ config('rifimedia_visuals.images.fallback_sports') }}">
                </a>
                <div class="rm-news-featured-card__body">
                    <span class="rm-story-card__label">{{ $featuredArticle->category?->name ?? __('Football') }}</span>
                    <h2><a href="{{ route('news.show', $featuredArticle->slug) }}">{{ $featuredArticle->title }}</a></h2>
                    @if($featuredArticle->excerpt)
                        <p>{{ $featuredArticle->excerpt }}</p>
                    @endif
                    <div class="rm-news-meta">
                        @if($featuredArticle->published_at)
                            <span><x-icon name="calendar" />{{ $featuredArticle->published_at->format('M j, Y') }}</span>
                        @endif
                        <span><x-icon name="clock" />{{ max(1, ceil(str_word_count(strip_tags($featuredArticle->body ?? $featuredArticle->excerpt ?? '')) / 220)) }} min read</span>
                    </div>
                    <a href="{{ route('news.show', $featuredArticle->slug) }}" class="rm-btn rm-btn-primary rm-btn-sm"><x-icon name="chevron-right" />{{ __("Read story") }}</a>
                </div>
            </article>
        </section>

        @if($remainingArticles->isNotEmpty())
            <section class="rm-section rm-news-section" aria-labelledby="rm-news-grid-title">
                <div class="rm-section-header">
                    <div>
                        <p class="rm-eyebrow">{{ __("Latest stories") }}</p>
                        <h2 id="rm-news-grid-title">{{ __("More from RiFiTV") }}</h2>
                    </div>
                </div>

                <div class="rm-news-grid">
                    @foreach($remainingArticles as $article)
                        <article class="rm-story-card" data-reveal>
                            <a href="{{ route('news.show', $article->slug) }}" class="rm-story-card__media" aria-label="Read {{ $article->title }}">
                                <img src="{{ $article->featured_image ?: config('rifimedia_visuals.images.fallback_sports') }}" alt="{{ $article->title }}" loading="lazy" data-fallback-src="{{ config('rifimedia_visuals.images.fallback_sports') }}">
                            </a>
                            <span class="rm-story-card__label">{{ $article->category?->name ?? __('Football') }}</span>
                            <h3><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h3>
                            @if($article->excerpt)
                                <p>{{ $article->excerpt }}</p>
                            @endif
                            <div class="rm-news-meta">
                                @if($article->published_at)
                                    <span><x-icon name="calendar" />{{ $article->published_at->format('M j, Y') }}</span>
                                @endif
                                <span><x-icon name="clock" />{{ max(1, ceil(str_word_count(strip_tags($article->body ?? $article->excerpt ?? '')) / 220)) }} min read</span>
                            </div>
                        </article>
                        @if($loop->iteration === 6)
                            <x-ad-slot name="news_in_feed" size="in-feed" />
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        <aside class="rm-side-rail rm-news-topics" aria-label="{{ __("Trending sports topics") }}">
            <div class="rm-topic-card">
                <h2>{{ __("Trending topics") }}</h2>
                <div class="rm-topic-cloud">
                    @foreach($topics as $topic)
                        <a href="{{ route('search', ['q' => $topic]) }}">{{ $topic }}</a>
                    @endforeach
                </div>
            </div>
        </aside>
    @endif
</div>
@endsection

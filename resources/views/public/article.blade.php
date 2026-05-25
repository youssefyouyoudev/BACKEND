@extends('layouts.app')

@section('title', ($article->meta_title ?: $article->title).' | RifiMedia')
@section('description', $article->meta_description ?: ($article->excerpt ?: 'Read football news and sports coverage on RifiMedia.'))

@section('content')
<article class="rm-page rm-page--article">
    <nav class="rm-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>/</span>
        <a href="{{ route('news.index') }}">News</a>
        <span>/</span>
        <strong>{{ $article->title }}</strong>
    </nav>

    <header class="rm-page-hero">
        <span class="rm-kicker">{{ $article->category?->name ?? 'Football' }}</span>
        <h1>{{ $article->title }}</h1>
        <p>{{ $article->excerpt }}</p>
        <small>{{ $article->published_at?->format('F j, Y') }} | {{ $article->author?->name ?? 'RifiMedia Desk' }}</small>
    </header>

    <x-ad-slot name="article_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div class="rm-readable-card rm-article-body">
            @if($article->featured_image)
                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" loading="lazy">
            @endif

            {!! nl2br(e($article->body)) !!}

            <x-ad-slot name="article_in_content" size="in-article" />
        </div>
        <aside class="rm-side-rail">
            <x-ad-slot name="article_sidebar_rectangle" size="rectangle" />
            @if($relatedArticles->isNotEmpty())
                <div class="rm-topic-card">
                    <h2>Related stories</h2>
                    @foreach($relatedArticles as $related)
                        <a href="{{ route('news.show', $related->slug) }}">{{ $related->title }}</a>
                    @endforeach
                </div>
            @endif
        </aside>
    </section>
</article>
@endsection

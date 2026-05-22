@extends('layouts.app')

@section('title', 'Search RifiMedia')
@section('description', 'Search real RifiMedia channels, published news, and public pages.')
@section('robots', 'noindex,follow')

@section('content')
<div class="rm-page rm-media-platform-page">
    <x-page-hero eyebrow="Search" title="Search RifiMedia" description="Find real channels, published news, and public pages.">
        <x-search-bar :value="$query" placeholder="Search channels, news, live TV, football" />
    </x-page-hero>

    @if($query === '')
        <x-empty-state title="Start searching" message="Enter a channel name, article topic, or page name to search RifiMedia." />
    @else
        <section class="rm-section">
            <x-section-header title="Channels" />
            @if($channels->count())
                <div class="rm-match-grid">
                    @foreach($channels as $channel)
                        <x-channel-card :channel="[
                            'id' => $channel->id,
                            'name' => $channel->clean_display_name,
                            'original_name' => $channel->name,
                            'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
                            'category' => $channel->group_title ?: 'Live TV',
                            'program' => ['title' => 'Live channel'],
                            'watch_url' => route('channels.show', $channel->slug ?: $channel->id),
                            'display_tags' => $channel->display_tags,
                            'quality_label' => $channel->quality_label,
                        ]" />
                    @endforeach
                </div>
            @else
                <x-empty-state title="No channels found" message="No approved public channels match this search." />
            @endif
        </section>

        <section class="rm-section">
            <x-section-header title="News" />
            @if($articles->count())
                <div class="rm-media-grid">
                    @foreach($articles as $article)
                        <x-media-card
                            :title="$article->title"
                            :description="$article->excerpt"
                            :href="route('news.show', $article->slug)"
                            :image="$article->featured_image"
                            label="News"
                        />
                    @endforeach
                </div>
            @else
                <x-empty-state title="No news found" message="No published article matches this search." />
            @endif
        </section>

        <section class="rm-section">
            <x-section-header title="Pages" />
            @if($pages->count())
                <div class="rm-media-grid">
                    @foreach($pages as $page)
                        <x-media-card :title="$page['title']" :description="$page['description']" :href="$page['url']" label="Page" />
                    @endforeach
                </div>
            @else
                <x-empty-state title="No pages found" message="No public page matched this search." />
            @endif
        </section>
    @endif
</div>
@endsection

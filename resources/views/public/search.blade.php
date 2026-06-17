@extends('layouts.app')

@section('title', __("Search RiFiTV"))
@section('description', __("Search RiFiTV teams, competitions, matches, football news, and public pages."))
@section('robots', 'noindex,follow')

@section('content')
<div class="rm-page rm-media-platform-page">
    <x-page-hero eyebrow="{{ __("Search") }}" title="{{ __("Search RiFiTV") }}" description="{{ __("Find teams, competitions, matches, news, and public pages.") }}">
        <x-search-bar :value="$query" placeholder="{{ __("Search teams, matches, competitions, or news") }}" />
    </x-page-hero>

    @if($query === '')
        <x-empty-state title="{{ __("Start searching") }}" message="{{ __("Enter a team, competition, match, article topic, or page name.") }}" />
    @else
        <section class="rm-section">
            <x-section-header title="{{ __("Channels") }}" />
            @if($channels->count())
                <div class="rm-match-grid">
                    @foreach($channels as $channel)
                        <x-channel-card :channel="[
                            'id' => $channel->id,
                            'name' => $channel->clean_display_name,
                            'original_name' => $channel->name,
                            'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
                            'category' => $channel->group_title ?: __('Live TV'),
                            'program' => ['title' => __('Live channel')],
                            'watch_url' => route('channels.show', $channel->slug ?: $channel->id),
                            'display_tags' => $channel->display_tags,
                            'quality_label' => $channel->quality_label,
                        ]" />
                    @endforeach
                </div>
            @else
                <x-empty-state title="{{ __("No channels found") }}" message="{{ __("No approved public channels match this search.") }}" />
            @endif
        </section>

        <section class="rm-section">
            <x-section-header title="{{ __("News") }}" />
            @if($articles->count())
                <div class="rm-media-grid">
                    @foreach($articles as $article)
                        <x-media-card
                            :title="$article->title"
                            :description="$article->excerpt"
                            :href="route('news.show', $article->slug)"
                            :image="$article->featured_image"
                            label="{{ __("News") }}"
                        />
                    @endforeach
                </div>
            @else
                <x-empty-state title="{{ __("No news found") }}" message="{{ __("No published article matches this search.") }}" />
            @endif
        </section>

        <section class="rm-section">
            <x-section-header title="{{ __("Pages") }}" />
            @if($pages->count())
                <div class="rm-media-grid">
                    @foreach($pages as $page)
                        <x-media-card :title="$page['title']" :description="$page['description']" :href="$page['url']" label="{{ __("Page") }}" />
                    @endforeach
                </div>
            @else
                <x-empty-state title="{{ __("No pages found") }}" message="{{ __("No public page matched this search.") }}" />
            @endif
        </section>
    @endif
</div>
@endsection

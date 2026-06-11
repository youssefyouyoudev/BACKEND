@extends('layouts.app')

@section('title', ($category?->name ?? __('Search')).' - RifiMedia Watch')

@section('content')
    <section class="iptv-shell">
        <aside class="iptv-sidebar">
            <x-logo />
            <nav>
                <a href="{{ route('watch.index') }}">{{ __("Home") }}</a>
                <a href="{{ route('watch.live') }}">{{ __("Live TV") }}</a>
                <a href="{{ route('watch.movies') }}">{{ __("Movies") }}</a>
                <a href="{{ route('watch.series') }}">{{ __("Series") }}</a>
            </nav>
        </aside>

        <div class="iptv-main">
            <header class="iptv-page-head">
                <div>
                    <p class="rm-eyebrow">{{ $category?->type ? str($category->type)->headline() : __('Search') }}</p>
                    <h1>{{ $category?->name ?? __('Search your IPTV library') }}</h1>
                </div>
                <form action="{{ $category ? route('watch.category', $category) : route('watch.search') }}" class="iptv-search">
                    <input name="q" value="{{ request('q') }}" placeholder="{{ __("Search this view") }}">
                    <button class="button button--primary">{{ __("Search") }}</button>
                </form>
            </header>

            @if($items->isEmpty())
                <div class="empty-state">
                    <h3>{{ __("No items found.") }}</h3>
                    <p>{{ __("Try another category or import a playlist with matching content.") }}</p>
                </div>
            @else
                <div class="iptv-card-grid">
                    @foreach($items as $item)
                        @include('watch.partials.item-card', ['item' => $item])
                    @endforeach
                </div>

                {{ $items->links() }}
            @endif
        </div>
    </section>
@endsection

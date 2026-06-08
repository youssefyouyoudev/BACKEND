@extends('layouts.admin')

@section('content')
<section class="page-header iptv-admin-hero">
    <div>
        <p class="page-header__eyebrow">Public catalog</p>
        <h1>IPTV item visibility</h1>
        <p class="page-header__copy">Search every imported IPTV item and control exactly what visitors can see on the public website.</p>
    </div>
    <a href="{{ route('live-tv') }}" class="button button--ghost" target="_blank" rel="noopener">Open public Live TV</a>
</section>

<section class="iptv-admin-stats" data-iptv-summary>
    <article><span>All items</span><strong data-summary-total>{{ number_format($summary['total']) }}</strong></article>
    <article><span>Public</span><strong data-summary-public>{{ number_format($summary['public']) }}</strong></article>
    <article><span>Hidden</span><strong data-summary-hidden>{{ number_format($summary['hidden']) }}</strong></article>
    <article><span>Matching filters</span><strong data-summary-filtered>{{ number_format($summary['filtered']) }}</strong></article>
</section>

<section
    class="surface-card iptv-admin-catalog"
    data-iptv-items-admin
    data-endpoint="{{ route('admin.iptv-items.index') }}"
    data-bulk-visibility-endpoint="{{ route('admin.iptv-items.visibility.all') }}"
>
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">Inventory</p>
            <h2>Imported IPTV items</h2>
        </div>
        <div class="iptv-admin-header-actions">
            <span class="iptv-admin-live-status" data-catalog-status aria-live="polite">Ready</span>
            <button class="button button--primary" type="button" data-bulk-visibility="1">Make all public</button>
            <button class="button button--danger" type="button" data-bulk-visibility="0">Hide all</button>
        </div>
    </div>

    <form class="iptv-admin-filters" data-iptv-filter-form>
        <label class="iptv-admin-search">
            <span>Search items</span>
            <div>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Channel, movie, series, TVG ID...">
                <button class="button button--primary" type="submit">Search</button>
            </div>
        </label>

        <label>
            <span>Type</span>
            <select name="type">
                <option value="">All types</option>
                <option value="live" @selected(request('type') === 'live')>Live TV</option>
                <option value="movie" @selected(request('type') === 'movie')>Movies</option>
                <option value="series" @selected(request('type') === 'series')>Series</option>
            </select>
        </label>

        <label>
            <span>Visibility</span>
            <select name="visibility">
                <option value="">Public and hidden</option>
                <option value="public" @selected(request('visibility') === 'public')>Public only</option>
                <option value="hidden" @selected(request('visibility') === 'hidden')>Hidden only</option>
            </select>
        </label>

        <label>
            <span>Playlist</span>
            <select name="playlist_id">
                <option value="">All playlists</option>
                @foreach($playlists as $playlist)
                    <option value="{{ $playlist->id }}" @selected((int) request('playlist_id') === $playlist->id)>{{ $playlist->name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Category</span>
            <select name="category_id">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) request('category_id') === $category->id)>{{ $category->name }} ({{ str($category->type)->headline() }})</option>
                @endforeach
            </select>
        </label>

        <button class="button button--ghost iptv-admin-reset" type="button" data-reset-filters>Reset filters</button>
    </form>

    <div class="iptv-admin-feedback" data-iptv-feedback hidden role="status"></div>

    <div class="table-shell iptv-admin-table-shell" data-iptv-results>
        <table class="data-table iptv-admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Playlist</th>
                    <th>Import status</th>
                    <th>Quality</th>
                    <th>Playback</th>
                    <th>Source</th>
                    <th>Public website</th>
                </tr>
            </thead>
            <tbody data-iptv-rows>
                @include('admin.iptv-items.partials.rows', ['items' => $items])
            </tbody>
        </table>
    </div>

    <div data-iptv-pagination>
        {{ $items->links() }}
    </div>
</section>
@endsection

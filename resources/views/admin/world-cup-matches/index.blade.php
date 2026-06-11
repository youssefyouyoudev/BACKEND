@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">Tournament operations</p>
        <h1>World Cup 2026 matches.</h1>
        <p class="page-header__copy">Assign channels, commentators, broadcast status, and approved watch links without changing your channel catalog.</p>
    </div>
    <a class="button button--primary" href="{{ route('admin.world-cup-matches.create') }}">Add match</a>
</section>

<section class="surface-card wc-admin-filters">
    <form method="GET" action="{{ route('admin.world-cup-matches.index') }}" class="wc-filter-grid">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Team, group, channel, commentator">
        </div>
        <div class="field">
            <label for="group">Group</label>
            <select id="group" name="group">
                <option value="">All groups</option>
                @foreach($groups as $group)
                    <option value="{{ $group }}" @selected(request('group') === $group)>{{ $group }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="sort">Kickoff order</label>
            <select id="sort" name="sort">
                <option value="asc" @selected(request('sort', 'asc') === 'asc')>Soonest first</option>
                <option value="desc" @selected(request('sort') === 'desc')>Latest first</option>
            </select>
        </div>
        <div class="wc-filter-checks">
            <label><input type="checkbox" name="missing_channel" value="1" @checked(request()->boolean('missing_channel'))> Missing channel</label>
            <label><input type="checkbox" name="missing_commentator" value="1" @checked(request()->boolean('missing_commentator'))> Missing commentator</label>
            <label><input type="checkbox" name="missing_live_link" value="1" @checked(request()->boolean('missing_live_link'))> Missing live link</label>
            <label><input type="checkbox" name="featured" value="1" @checked(request()->boolean('featured'))> Featured only</label>
        </div>
        <div class="wc-filter-actions">
            <button class="button button--primary" type="submit">Apply filters</button>
            <a class="button button--ghost" href="{{ route('admin.world-cup-matches.index') }}">Reset</a>
        </div>
    </form>
</section>

<section
    class="surface-card"
    data-world-cup-admin
    data-iptv-search-endpoint="{{ route('admin.world-cup-matches.iptv-items') }}"
>
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">Group stage</p>
            <h2>{{ number_format($matches->total()) }} matches</h2>
        </div>
    </div>

    <div class="wc-admin-match-list">
        @forelse($matches as $match)
            <article
                class="wc-admin-match-card"
                data-match-card
                data-assign-iptv-endpoint="{{ route('admin.world-cup-matches.assign-iptv-item', $match) }}"
                data-assigned-iptv-ids='@json($match->iptvItems->pluck("id")->values())'
            >
                <div class="wc-admin-match-card__time">
                    <span>{{ $match->group_name }}</span>
                    <strong>{{ $match->morocco_kickoff_at?->format('H:i') ?? '--:--' }}</strong>
                    <small>{{ $match->morocco_kickoff_at?->format('M d, Y') }} Morocco</small>
                </div>
                <div class="wc-admin-match-card__teams">
                    <small>Match {{ $match->match_number }}</small>
                    <h3>{{ $match->home_team }} <span>vs</span> {{ $match->away_team }}</h3>
                    <p>{{ $match->venue }}, {{ $match->city }}</p>
                </div>
                <div class="wc-admin-match-card__broadcast">
                    <span class="status-pill status-pill--{{ str($match->broadcast_status)->slug('-') }}">{{ str($match->broadcast_status)->headline() }}</span>
                    <strong data-assigned-iptv-name>{{ $match->public_channel_name }}</strong>
                    <small>{{ $match->commentator ?: 'Commentator to be confirmed' }}</small>
                    <small
                        data-watch-availability
                        data-scheduled-text="Automatically unlocks {{ $match->watch_available_at?->timezone('Africa/Casablanca')->format('M d, H:i') }} Morocco"
                    >
                        @if($match->iptvItems->isNotEmpty() || $match->selectedIptvItem)
                            {{ $match->is_watch_window_open
                                ? 'Watch links are available now'
                                : 'Automatically unlocks '.$match->watch_available_at?->timezone('Africa/Casablanca')->format('M d, H:i').' Morocco' }}
                        @else
                            Choose one or more public IPTV channels
                        @endif
                    </small>
                </div>
                <div class="wc-admin-match-card__actions">
                    <a class="button button--primary" href="{{ route('admin.world-cup-matches.edit', $match) }}">Edit</a>
                    <button
                        class="button button--ghost"
                        type="button"
                        data-iptv-picker-toggle
                        aria-expanded="false"
                    >Assign IPTV channels</button>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="toggle_live">
                        <button class="button button--ghost" type="submit">{{ $match->is_live_link_enabled ? 'Disable live' : 'Enable live' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="toggle_featured">
                        <button class="button button--ghost" type="submit">{{ $match->is_featured ? 'Unfeature' : 'Feature' }}</button>
                    </form>
                </div>
                <div class="wc-iptv-picker" data-iptv-picker hidden>
                    <div class="field">
                        <label for="iptv-search-{{ $match->id }}">Public active IPTV channels</label>
                        <input
                            id="iptv-search-{{ $match->id }}"
                            type="search"
                            placeholder="Search beIN, Arryadia, SSC…"
                            autocomplete="off"
                            data-iptv-search
                        >
                    </div>
                    <div class="wc-iptv-results" data-iptv-results></div>
                    <div class="wc-iptv-picker__footer">
                        <small data-iptv-status>Search or select from the first public channels.</small>
                        <button
                            class="button button--danger"
                            type="button"
                            data-iptv-clear
                            @if($match->iptvItems->isEmpty() && ! $match->selectedIptvItem) hidden @endif
                        >Remove all IPTV</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h3>No matches found.</h3>
                <p>Adjust the filters or import the group-stage seeder.</p>
            </div>
        @endforelse
    </div>

    {{ $matches->links() }}
</section>
@endsection

@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("Tournament operations") }}</p>
        <h1>{{ __("World Cup 2026 matches.") }}</h1>
        <p class="page-header__copy">{{ __("Assign channels, commentators, broadcast status, and approved watch links without changing your channel catalog.") }}</p>
    </div>
    <div class="page-header__actions">
        <form method="POST" action="{{ route('admin.world-cup-matches.auto-end-old') }}">
            @csrf
            <button class="button button--ghost" type="submit">{{ __("Mark old matches finished") }}</button>
        </form>
        <a class="button button--primary" href="{{ route('admin.world-cup-matches.create') }}">{{ __("Add match") }}</a>
    </div>
</section>

<section class="surface-card wc-admin-filters">
    <form method="GET" action="{{ route('admin.world-cup-matches.index') }}" class="wc-filter-grid">
        <div class="field">
            <label for="search">{{ __("Search") }}</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="{{ __("Team, group, channel, commentator") }}">
        </div>
        <div class="field">
            <label for="group">{{ __("Group") }}</label>
            <select id="group" name="group">
                <option value="">{{ __("All groups") }}</option>
                @foreach($groups as $group)
                    <option value="{{ $group }}" @selected(request('group') === $group)>{{ $group }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">{{ __("Status") }}</label>
            <select id="status" name="status">
                <option value="">{{ __("All statuses") }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="sort">{{ __("Kickoff order") }}</label>
            <select id="sort" name="sort">
                <option value="asc" @selected(request('sort', 'asc') === 'asc')>{{ __("Soonest first") }}</option>
                <option value="desc" @selected(request('sort') === 'desc')>{{ __("Latest first") }}</option>
            </select>
        </div>
        <div class="wc-filter-checks">
            <label><input type="checkbox" name="missing_channel" value="1" @checked(request()->boolean('missing_channel'))> {{ __("Missing channel") }}</label>
            <label><input type="checkbox" name="missing_commentator" value="1" @checked(request()->boolean('missing_commentator'))> {{ __("Missing commentator") }}</label>
            <label><input type="checkbox" name="missing_live_link" value="1" @checked(request()->boolean('missing_live_link'))> {{ __("Missing live link") }}</label>
            <label><input type="checkbox" name="featured" value="1" @checked(request()->boolean('featured'))> {{ __("Featured only") }}</label>
        </div>
        <div class="wc-filter-actions">
            <button class="button button--primary" type="submit">{{ __("Apply filters") }}</button>
            <a class="button button--ghost" href="{{ route('admin.world-cup-matches.index') }}">{{ __("Reset") }}</a>
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
            <p class="surface-card__eyebrow">{{ __("World Cup matches") }}</p>
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
                    <h3>{{ $match->home_display_name }} <span>{{ __("vs") }}</span> {{ $match->away_display_name }}</h3>
                    <p>{{ $match->venue }}, {{ $match->city }}</p>
                </div>
                <div class="wc-admin-match-card__broadcast">
                    <span class="status-pill status-pill--{{ str($match->public_status)->slug('-') }}">{{ $match->public_status_label }}</span>
                    <strong data-assigned-iptv-name>{{ $match->public_channel_name }}</strong>
                    <small>{{ $match->commentator ?: __('Commentator to be confirmed') }}</small>
                    <small
                        data-watch-availability
                        data-scheduled-text="Automatically opens {{ $match->watch_opens_at?->format('M d, H:i') }} Morocco"
                    >
                        @if($match->iptvItems->isNotEmpty() || $match->selectedIptvItem)
                            {{ $match->is_watch_window_open
                                ? __('Watch links are available now')
                                : __('Automatically opens :date Morocco', ['date' => $match->watch_opens_at?->format('M d, H:i')]) }}
                        @else
                            Choose one or more public IPTV channels
                        @endif
                    </small>
                    <small>{{ __("Expires at") }} {{ $match->watch_expires_at?->format('M d, H:i') }} · {{ str($match->watchStatus())->headline() }}</small>
                </div>
                <div class="wc-admin-match-card__actions">
                    <a class="button button--primary" href="{{ route('admin.world-cup-matches.edit', $match) }}">{{ __("Edit") }}</a>
                    @if($match->is_knockout || ((int) $match->match_number >= 73 && (int) $match->match_number <= 104))
                        <a class="button button--ghost" href="{{ route('admin.world-cup-matches.result.edit', $match) }}">{{ __("Result") }}</a>
                    @endif
                    <button
                        class="button button--ghost"
                        type="button"
                        data-iptv-picker-toggle
                        aria-expanded="false"
                    >{{ __("Assign IPTV channels") }}</button>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="enable_live">
                        <button class="button button--ghost" type="submit">{{ __("Enable Live") }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="disable_live">
                        <button class="button button--ghost" type="submit">{{ __("Disable Live") }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="mark_live">
                        <button class="button button--ghost" type="submit">{{ __("Mark as Live") }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="mark_ended">
                        <button class="button button--ghost" type="submit">{{ __("Mark as Ended") }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.world-cup-matches.quick-update', $match) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="toggle_featured">
                        <button class="button button--ghost" type="submit">{{ $match->is_featured ? __('Unfeature') : __('Feature') }}</button>
                    </form>
                </div>
                <div class="wc-iptv-picker" data-iptv-picker hidden>
                    <div class="field">
                        <label for="iptv-search-{{ $match->id }}">{{ __("Public active IPTV channels") }}</label>
                        <input
                            id="iptv-search-{{ $match->id }}"
                            type="search"
                            placeholder="{{ __("Search beIN, Arryadia, SSC…") }}"
                            autocomplete="off"
                            data-iptv-search
                        >
                    </div>
                    <div class="wc-iptv-results" data-iptv-results></div>
                    <div class="wc-iptv-picker__footer">
                        <small data-iptv-status>{{ __("Search or select from the first public channels.") }}</small>
                        <button
                            class="button button--danger"
                            type="button"
                            data-iptv-clear
                            @if($match->iptvItems->isEmpty() && ! $match->selectedIptvItem) hidden @endif
                        >{{ __("Remove all IPTV") }}</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h3>{{ __("No matches found.") }}</h3>
                <p>{{ __("Adjust the filters or import the group-stage seeder.") }}</p>
            </div>
        @endforelse
    </div>

    {{ $matches->links() }}
</section>
@endsection

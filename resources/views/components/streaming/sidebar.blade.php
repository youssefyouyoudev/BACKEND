@props([
    'active' => 'home',
    'recommendedChannels' => collect(),
])

<aside class="stream-sidebar" aria-label="Streaming navigation">
    <div class="stream-sidebar__top">
        <a href="{{ route('home') }}" class="stream-logo" aria-label="RiFi Media TV home">
            <span class="stream-logo__mark">
                <span></span><span></span>
            </span>
            <strong>RIFI</strong>
        </a>

        <button type="button" class="stream-sidebar__menu" aria-label="Collapse menu">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>

    <nav class="stream-nav" aria-label="Main menu">
        <a href="{{ route('home') }}" class="{{ $active === 'home' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none"><path d="m4 11 8-7 8 7v8a2 2 0 0 1-2 2h-3v-6H9v6H6a2 2 0 0 1-2-2v-8Z"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('home', ['sort' => 'trending']) }}" class="{{ $active === 'trending' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none"><path d="m4 17 6-6 4 4 6-8"/><path d="M14 7h6v6"/></svg>
            <span>Trending</span>
        </a>
        <a href="{{ route('live-tv') }}#following" class="{{ $active === 'following' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none"><path d="M16 11a4 4 0 1 0-8 0"/><path d="M3 21c1.8-4 4.8-6 9-6 1.9 0 3.5.4 4.8 1.2"/><path d="M18 16v5M15.5 18.5h5"/></svg>
            <span>Following</span>
        </a>
        <a href="{{ route('live-tv') }}" class="{{ $active === 'live' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="6" width="14" height="12" rx="3"/><path d="m10 10 5 2-5 2v-4Z"/></svg>
            <span>My Channels</span>
        </a>
    </nav>

    <section class="recommended-list" aria-label="Recommended channels">
        <h2>Recommended Channels</h2>
        <div class="recommended-list__items">
            @forelse($recommendedChannels as $channel)
                <a href="{{ route('live-tv') }}?channel={{ $channel['id'] ?? $channel->id }}" class="recommended-channel">
                    <img
                        src="{{ $channel['avatar'] ?? $channel['logo'] ?? asset('brand/rifi-logo.png') }}"
                        alt=""
                        loading="lazy"
                        onerror="this.src='{{ asset('brand/rifi-logo.png') }}'"
                    >
                    <span>
                        <strong>{{ $channel['name'] ?? $channel->name }}</strong>
                        <em>{{ $channel['category'] ?? $channel['group_title'] ?? 'General' }}</em>
                    </span>
                    <small><i></i>{{ $channel['viewers_label'] ?? '1.2K' }}</small>
                </a>
            @empty
                <p class="recommended-list__empty">Import and approve playlists to populate recommendations.</p>
            @endforelse
        </div>
    </section>
</aside>

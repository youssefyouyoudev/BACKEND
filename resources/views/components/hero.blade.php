@props(['channel' => null])

@php
    $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: asset('brand/rifi-logo.png');
    $name = data_get($channel, 'name', 'RiFi Media Live');
    $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'Premium sports TV';
    $program = data_get($channel, 'program.title') ?: 'Live matches, channels, and featured broadcasts';
    $watchUrl = $channel ? (data_get($channel, 'watch_url') ?: route('channels.show', data_get($channel, 'id'))) : route('live');
@endphp

<section class="rm-hero" aria-labelledby="rm-hero-title">
    <div class="rm-hero__media" aria-hidden="true">
        <img src="{{ $logo }}" alt="" loading="eager" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
    </div>

    <div class="rm-hero__content">
        <span class="rm-live-badge"><i></i> Live now</span>
        <p class="rm-eyebrow">Featured broadcast</p>
        <h1 id="rm-hero-title">{{ $name }}</h1>
        <p class="rm-hero__copy">{{ $category }} - {{ $program }}</p>
        <div class="rm-hero__actions">
            <a href="{{ $watchUrl }}" class="rm-btn rm-btn-primary">Watch Live</a>
            <a href="#channels" class="rm-btn rm-btn-secondary">Browse Matches</a>
        </div>
    </div>

    <aside class="rm-hero-card" aria-label="Featured channel preview">
        <span class="rm-hero-card__screen">
            <img src="{{ $logo }}" alt="{{ $name }}" loading="eager" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
        </span>
        <span class="rm-hero-card__meta">
            <span>
                <strong>{{ $name }}</strong>
                <small>{{ $category }}</small>
            </span>
            <em>HD</em>
        </span>
    </aside>
</section>

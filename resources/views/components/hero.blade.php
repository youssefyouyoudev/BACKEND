@props(['channel' => null])

@php
    $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: asset('brand/rifi-logo.png');
    $name = data_get($channel, 'name', 'RiFi Media Live');
    $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'Premium IPTV';
    $program = data_get($channel, 'program.title') ?: 'Satellite-style live channels';
    $watchUrl = $channel ? (data_get($channel, 'watch_url') ?: route('channels.show', data_get($channel, 'id'))) : route('live');
@endphp

<section class="sat-hero">
    <div class="sat-hero__backdrop">
        <img src="{{ $logo }}" alt="" loading="eager" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
    </div>
    <div class="sat-hero__content">
        <span class="sat-kicker"><i></i> Featured live channel</span>
        <h1>{{ $name }}</h1>
        <p>{{ $category }} · {{ $program }}</p>
        <div class="sat-hero__actions">
            <a href="{{ $watchUrl }}" class="sat-button sat-button--primary">Watch Live</a>
            <a href="#channels" class="sat-button sat-button--ghost">Browse Channels</a>
        </div>
    </div>
    <div class="sat-hero__preview">
        <img src="{{ $logo }}" alt="{{ $name }}" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
        <span><i></i> On Air</span>
    </div>
</section>

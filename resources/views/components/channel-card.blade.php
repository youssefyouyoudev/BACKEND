@props(['channel'])

@php
    $name = data_get($channel, 'name');
    $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: asset('brand/rifi-logo.png');
    $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'General';
    $program = data_get($channel, 'program.title') ?: data_get($channel, 'current_program');
    $url = data_get($channel, 'watch_url') ?: route('channels.show', data_get($channel, 'id'));
@endphp

<article class="sat-channel-card" data-channel-card>
    <a href="{{ $url }}" class="sat-channel-card__link">
        <span class="sat-channel-card__art">
            <img src="{{ $logo }}" alt="{{ $name }}" loading="lazy" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
            <em><i></i> Live</em>
            <b>{{ $category }}</b>
        </span>
        <span class="sat-channel-card__body">
            <strong>{{ $name }}</strong>
            <small>{{ $program ?: 'Live broadcast' }}</small>
        </span>
    </a>
</article>

@props(['channel'])

@php
    $name = data_get($channel, 'name', 'Live channel');
    $originalName = data_get($channel, 'original_name', $name);
    $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: data_get($channel, 'avatar') ?: asset('brand/rifi-logo.png');
    $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'General';
    $program = data_get($channel, 'program.title') ?: data_get($channel, 'current_program') ?: 'Live broadcast';
    $url = data_get($channel, 'watch_url') ?: route('channels.show', data_get($channel, 'id'));
    $viewers = data_get($channel, 'viewers_label');
    $tags = collect(data_get($channel, 'display_tags', []))->take(3);
    $quality = data_get($channel, 'quality_label') ?: $tags->first(fn ($tag) => in_array($tag, ['4K', 'UHD', 'FHD', 'HD', 'SD'], true)) ?: 'HD';
@endphp

<article class="rm-match-card" data-channel-card>
    <a href="{{ $url }}" class="rm-match-card__link" aria-label="Watch {{ $originalName }} live">
        <span class="rm-match-card__poster">
            <img src="{{ $logo }}" alt="{{ $name }}" loading="lazy" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
            <span class="rm-live-badge rm-live-badge--small"><i></i> Live</span>
            <span class="rm-match-card__quality">{{ $quality }}</span>
        </span>
        <span class="rm-match-card__body">
            <span class="rm-match-card__league">{{ $category }}</span>
            <strong>{{ $name }}</strong>
            @if($tags->isNotEmpty())
                <span class="rm-match-card__tags">
                    @foreach($tags as $tag)
                        <b>{{ $tag }}</b>
                    @endforeach
                </span>
            @endif
            <small>{{ $program }}</small>
            <span class="rm-match-card__footer">
                <span>{{ $viewers ? $viewers.' watching' : 'On air now' }}</span>
                <em>Watch</em>
            </span>
        </span>
    </a>
</article>

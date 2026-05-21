@props(['channel'])

@php
    $name = data_get($channel, 'name', 'Live channel');
    $originalName = data_get($channel, 'original_name', $name);
    $displayName = (string) preg_replace('/^\s*(?:\|?([A-Z]{2,4})\|?\s*)+/iu', '', (string) $name);
    $displayName = (string) preg_replace('/\b(?:AR|FR|EN|UHD|FHD|HD|SD|4K|VIP)\b/iu', '', $displayName);
    $displayName = (string) preg_replace('/\s+/u', ' ', trim($displayName));
    $displayName = $displayName !== '' ? $displayName : $name;
    $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: data_get($channel, 'avatar') ?: asset('brand/rifi-logo.png');
    $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'General';
    $program = data_get($channel, 'program.title') ?: data_get($channel, 'current_program') ?: 'Channel guide';
    $url = data_get($channel, 'watch_url') ?: route('channels.show', data_get($channel, 'id'));
    $viewers = data_get($channel, 'viewers_label');
    $rawTags = collect(data_get($channel, 'display_tags', []));
    if ($rawTags->isEmpty() && preg_match_all('/\b(AR|FR|EN|UHD|FHD|HD|SD|4K|VIP)\b/iu', (string) $originalName, $matches)) {
        $rawTags = collect($matches[1])->map(fn ($tag) => strtoupper($tag));
    }
    $tags = $rawTags->unique()->take(3);
    $quality = data_get($channel, 'quality_label') ?: $tags->first(fn ($tag) => in_array($tag, ['4K', 'UHD', 'FHD', 'HD', 'SD'], true)) ?: 'HD';
@endphp

<article class="rm-match-card rm-channel-card" data-channel-card>
    <a href="{{ $url }}" class="rm-match-card__link" aria-label="Open {{ $originalName }} channel information">
        <span class="rm-match-card__poster">
            <img src="{{ $logo }}" alt="{{ $displayName }}" loading="lazy" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
            <span class="rm-live-badge rm-live-badge--small"><i></i> On air</span>
            <span class="rm-match-card__quality">{{ $quality }}</span>
        </span>
        <span class="rm-match-card__body">
            <span class="rm-match-card__league">{{ $category }}</span>
            <strong>{{ $displayName }}</strong>
            @if($tags->isNotEmpty())
                <span class="rm-match-card__tags">
                    @foreach($tags as $tag)
                        <b>{{ $tag }}</b>
                    @endforeach
                </span>
            @endif
            <small>{{ $program }}</small>
            <span class="rm-match-card__footer">
                <span>{{ $viewers ? $viewers.' viewers' : 'Media channel' }}</span>
                <em>Open</em>
            </span>
        </span>
    </a>
</article>

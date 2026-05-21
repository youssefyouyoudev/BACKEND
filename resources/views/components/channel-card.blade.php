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
    <a href="{{ $url }}" class="rm-match-card__link" aria-label="Open {{ $displayName }} — {{ $category }}">

        {{-- Poster / logo area --}}
        <span class="rm-match-card__poster">
            <img
                src="{{ $logo }}"
                alt="{{ $displayName }} logo"
                loading="lazy"
                decoding="async"
                onerror="this.src='{{ asset('brand/rifi-logo.png') }}'"
            >
            {{-- Live badge --}}
            <span class="rm-live-badge rm-live-badge--small" aria-label="On air">
                <i aria-hidden="true"></i> On air
            </span>
            {{-- Quality badge --}}
            <span class="rm-match-card__quality" aria-label="Quality: {{ $quality }}">{{ $quality }}</span>
        </span>

        {{-- Card body --}}
        <span class="rm-match-card__body">
            {{-- Category label --}}
            <span class="rm-match-card__league">{{ $category }}</span>

            {{-- Channel name --}}
            <strong title="{{ $displayName }}">{{ $displayName }}</strong>

            {{-- Quality / language tags --}}
            @if($tags->isNotEmpty())
                <span class="rm-match-card__tags" aria-label="Stream tags">
                    @foreach($tags as $tag)
                        <b>{{ $tag }}</b>
                    @endforeach
                </span>
            @else
                <span class="rm-match-card__tags"></span>
            @endif

            {{-- Program guide --}}
            <small title="{{ $program }}">{{ $program }}</small>

            {{-- Footer: viewers + open button --}}
            <span class="rm-match-card__footer">
                <span>{{ $viewers ? $viewers . ' viewers' : 'Media channel' }}</span>
                <em aria-hidden="true">Open →</em>
            </span>
        </span>

    </a>
</article>

@props([
    'priority' => false,
    'compact' => false,
    'linked' => true,
])

@php
    $base = asset('assets/images/promo/rifitv-world-football-2026');
    $alt = app()->isLocale('ar')
        ? 'عرض RiFiTV لموسم كرة القدم العالمي 2026'
        : 'RiFiTV 2026 world football season promotion';
@endphp

<figure
    {{ $attributes->class(['rm-promo-banner', 'rm-promo-banner--compact' => $compact]) }}
    data-legacy-source="{{ asset('assets/images/fifa_world_cup_2026_tease.png') }}"
>
    @if($linked)
        <a href="{{ config('ads.sponsor_url') }}" target="_blank" rel="nofollow sponsored noopener noreferrer" aria-label="{{ $alt }}">
    @endif
    <picture>
        <source
            type="image/avif"
            srcset="{{ $base }}-480.avif 480w, {{ $base }}-768.avif 768w, {{ $base }}-1122.avif 1122w"
            sizes="{{ $compact ? '(max-width: 767px) 92vw, 480px' : '(max-width: 767px) 94vw, (max-width: 1180px) 52vw, 560px' }}"
        >
        <source
            type="image/webp"
            srcset="{{ $base }}-480.webp 480w, {{ $base }}-768.webp 768w, {{ $base }}-1122.webp 1122w"
            sizes="{{ $compact ? '(max-width: 767px) 92vw, 480px' : '(max-width: 767px) 94vw, (max-width: 1180px) 52vw, 560px' }}"
        >
        <img
            src="{{ $base }}-768.webp"
            alt="{{ $alt }}"
            width="1122"
            height="1402"
            loading="{{ $priority ? 'eager' : 'lazy' }}"
            fetchpriority="{{ $priority ? 'high' : 'auto' }}"
            decoding="async"
        >
    </picture>
    @if($linked)
        </a>
    @endif
</figure>

@props([
    'name' => null,
    'placement' => null,
    'size' => null,
    'type' => 'banner',
    'label' => null,
    'href' => null,
    'image' => null,
    'compact' => false,
    'showDirectLink' => true,
])

@php
    $legacyType = match ($size) {
        'rectangle' => 'card',
        'sidebar' => 'sidebar',
        'in-feed', 'in-article' => 'inline',
        default => 'banner',
    };
    $slotType = $size ? $legacyType : $type;
    $slotName = $placement ?: ($name ?: $slotType);
    $slotLabel = $label ?: (app()->isLocale('ar') ? 'إعلان' : 'Sponsored');
    $slotHref = $href ?: config('ads.sponsor_url');
    $isCompact = filter_var($compact, FILTER_VALIDATE_BOOL);
    $hasDirectLink = filter_var($showDirectLink, FILTER_VALIDATE_BOOL);
@endphp

@if(config('ads.enabled') && !request()->is('admin*'))
<aside
    {{ $attributes->class([
        'rm-ad-slot',
        'rifitv-ad-slot',
        'rm-ad-slot--'.$slotType,
        'rm-ad-slot--compact' => $isCompact,
        'rifitv-ad-slot--compact' => $isCompact,
    ]) }}
    data-ad-slot="{{ $slotName }}"
    data-ad-placement="{{ $slotName }}"
    aria-label="{{ $slotLabel }}"
    role="complementary"
>
    @if($slotType === 'sticky')
        <button type="button" class="rm-ad-slot__close" data-ad-dismiss aria-label="{{ app()->isLocale('ar') ? 'إغلاق الإعلان' : 'Close sponsored message' }}">×</button>
    @endif

    @if($hasDirectLink)
        <a
            class="rm-ad-slot__link rifitv-ad-link"
            href="{{ $slotHref }}"
            target="_blank"
            rel="nofollow sponsored noopener noreferrer"
        >
    @else
        <div class="rm-ad-slot__link">
    @endif
        @if($image)
            <img src="{{ $image }}" alt="{{ app()->isLocale('ar') ? 'عرض RiFiTV الرياضي' : 'RiFiTV sports offer' }}" loading="lazy" decoding="async">
        @endif
        <span class="rm-ad-slot__copy">
            <small class="rifitv-ad-label">{{ $slotLabel }}</small>
            <strong>{{ app()->isLocale('ar') ? 'عيش أجواء الماتش من دارك' : 'Bring matchday home' }}</strong>
            <span>{{ app()->isLocale('ar') ? 'اكتشف العرض الرياضي المميز' : 'Discover the premium sports offer' }}</span>
        </span>
        <b>{{ app()->isLocale('ar') ? 'سجّل دابا' : 'See offer' }}</b>
    @if($hasDirectLink)
        </a>
    @else
        </div>
    @endif
</aside>
@endif

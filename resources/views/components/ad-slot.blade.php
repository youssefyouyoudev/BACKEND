@props([
    'name' => null,
    'size' => null,
    'type' => 'banner',
    'label' => null,
    'href' => null,
    'image' => null,
    'compact' => false,
])

@php
    $legacyType = match ($size) {
        'rectangle' => 'card',
        'sidebar' => 'sidebar',
        'in-feed', 'in-article' => 'inline',
        default => 'banner',
    };
    $slotType = $size ? $legacyType : $type;
    $slotName = $name ?: $slotType;
    $slotLabel = $label ?: (app()->isLocale('ar') ? 'إعلان' : 'Sponsored');
    $slotHref = $href ?: config('ads.sponsor_url');
    $isCompact = filter_var($compact, FILTER_VALIDATE_BOOL);
@endphp

<aside
    {{ $attributes->class([
        'rm-ad-slot',
        'rm-ad-slot--'.$slotType,
        'rm-ad-slot--compact' => $isCompact,
    ]) }}
    data-ad-slot="{{ $slotName }}"
    aria-label="{{ $slotLabel }}"
    role="complementary"
>
    @if($slotType === 'sticky')
        <button type="button" class="rm-ad-slot__close" data-ad-dismiss aria-label="{{ app()->isLocale('ar') ? 'إغلاق الإعلان' : 'Close sponsored message' }}">×</button>
    @endif

    <a
        class="rm-ad-slot__link"
        href="{{ $slotHref }}"
        target="_blank"
        rel="nofollow sponsored noopener noreferrer"
    >
        @if($image)
            <img src="{{ $image }}" alt="{{ app()->isLocale('ar') ? 'عرض RiFiTV الرياضي' : 'RiFiTV sports offer' }}" loading="lazy" decoding="async">
        @endif
        <span class="rm-ad-slot__copy">
            <small>{{ $slotLabel }}</small>
            <strong>{{ app()->isLocale('ar') ? 'عيش أجواء الماتش من دارك' : 'Bring matchday home' }}</strong>
            <span>{{ app()->isLocale('ar') ? 'اكتشف العرض الرياضي المميز' : 'Discover the premium sports offer' }}</span>
        </span>
        <b>{{ app()->isLocale('ar') ? 'سجّل دابا' : 'See offer' }}</b>
    </a>
</aside>

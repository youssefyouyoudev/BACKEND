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
    $slotLabel = $label ?: __('Advertisement');
    $adSetting = \App\Models\AdSetting::enabledForPlacement($slotName);
    $slotHref = $href ?: $adSetting?->direct_link_url ?: config('ads.sponsor_url');
    $isCompact = filter_var($compact, FILTER_VALIDATE_BOOL);
    $hasDirectLink = filter_var($showDirectLink, FILTER_VALIDATE_BOOL) && filled($slotHref);
    $shouldRender = config('ads.enabled') && !request()->is('admin*') && ($adSetting || app()->environment('local'));
@endphp

@if($shouldRender)
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
    data-frequency-seconds="{{ $adSetting?->frequency_seconds ?? 0 }}"
    data-max-per-session="{{ $adSetting?->max_per_session ?? 10 }}"
    aria-label="{{ $slotLabel }}"
    role="complementary"
>
    @if($slotType === 'sticky')
        <button type="button" class="rm-ad-slot__close" data-ad-dismiss aria-label="{{ __('Close advertisement') }}">&times;</button>
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
            <img src="{{ $image }}" alt="{{ __('RiFiTV sports offer') }}" loading="lazy" decoding="async">
        @endif
        <span class="rm-ad-slot__copy">
            <small class="rifitv-ad-label">{{ $slotLabel }}</small>
            <strong>{{ __('Support RiFiTV') }}</strong>
            <span>{{ __('Sponsored matchday offer') }}</span>
        </span>
        <b>{{ __('Open') }}</b>
    @if($hasDirectLink)
        </a>
    @else
        </div>
    @endif
</aside>
@endif

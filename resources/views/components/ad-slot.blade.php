@props([
    'name',
    'size' => 'leaderboard',
    'label' => 'Advertisement',
])

@php
    $isEnabled = config('ads.enabled');
@endphp

<aside
    class="rm-ad-slot rm-ad-slot--{{ $size }} ad-slot ad-slot-{{ $size }}"
    data-ad-slot="{{ $name }}"
    aria-label="{{ $label }}"
    role="complementary"
>
    @if($isEnabled && config('ads.provider') === 'adsense' && config('ads.adsense_client'))
        <span aria-hidden="true">{{ $label }}</span>
        <ins
            class="adsbygoogle"
            style="display:block"
            data-ad-client="{{ config('ads.adsense_client') }}"
            data-ad-slot="{{ $name }}"
            data-ad-format="auto"
            data-full-width-responsive="true"
        ></ins>
    @else
        <p class="rm-ad-label" aria-hidden="true">{{ $label }}</p>
        <strong>Reserved Media Placement</strong>
        <small>{{ ucwords(str_replace(['_', '-'], ' ', $size)) }} · Premium inventory</small>
    @endif
</aside>

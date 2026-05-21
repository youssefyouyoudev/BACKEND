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
>
    <span>{{ $label }}</span>
    @if($isEnabled && config('ads.provider') === 'adsense' && config('ads.adsense_client'))
        <ins
            class="adsbygoogle"
            style="display:block"
            data-ad-client="{{ config('ads.adsense_client') }}"
            data-ad-slot="{{ $name }}"
            data-ad-format="auto"
            data-full-width-responsive="true"
        ></ins>
    @else
        <strong>Reserved media placement</strong>
        <small>{{ ucfirst(str_replace('_', ' ', $size)) }} slot</small>
    @endif
</aside>

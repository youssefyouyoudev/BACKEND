@props([
    'href' => route('home'),
    'compact' => false,
])

<a href="{{ $href }}" class="brand-mark rm-brand {{ $compact ? 'brand-mark--compact rm-brand--compact' : '' }}" aria-label="RifiMedia Sports home">
    <img src="{{ asset('brand/rifi-logo.png') }}" alt="RifiMedia Sports logo" class="brand-mark__image rm-brand__image">
    <span class="brand-mark__text rm-brand__text">
        <span class="brand-mark__name">RiFi</span>
        <span class="brand-mark__accent">MEDIA</span>
        <span class="brand-mark__suffix">Sports</span>
    </span>
</a>

@props([
    'href' => route('home'),
    'compact' => false,
])

<a href="{{ $href }}" class="brand-mark rm-brand {{ $compact ? 'brand-mark--compact rm-brand--compact' : '' }}" aria-label="RifiMedia home">
    <img src="{{ asset('brand/rifi-logo.png') }}" alt="RifiMedia logo" class="brand-mark__image rm-brand__image">
    <span class="brand-mark__text rm-brand__text">
        <span class="brand-mark__name">Rifi</span>
        <span class="brand-mark__accent">Media</span>
    </span>
</a>

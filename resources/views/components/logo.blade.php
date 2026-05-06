@props([
    'href' => route('home'),
    'compact' => false,
])

<a href="{{ $href }}" class="brand-mark {{ $compact ? 'brand-mark--compact' : '' }}">
    <img src="{{ asset('brand/rifi-logo.png') }}" alt="RiFi Media TV logo" class="brand-mark__image">
    <span class="brand-mark__text">
        <span class="brand-mark__name">RiFi</span>
        <span class="brand-mark__accent">MEDIA</span>
        <span class="brand-mark__suffix">TV</span>
    </span>
</a>

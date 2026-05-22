@props(['label' => 'Live'])

<span {{ $attributes->merge(['class' => 'rm-live-badge']) }}>
    <i aria-hidden="true"></i> {{ $label }}
</span>

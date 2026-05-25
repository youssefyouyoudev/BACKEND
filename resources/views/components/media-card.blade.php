@props(['title', 'description' => null, 'href' => null, 'label' => null, 'image' => null, 'disabled' => false])

<article {{ $attributes->merge(['class' => 'rm-media-card'.($disabled ? ' is-disabled' : '')]) }}>
    <img src="{{ $image ?: config('rifimedia_visuals.images.fallback_sports') }}" alt="{{ $title }}" loading="lazy" data-fallback-src="{{ config('rifimedia_visuals.images.fallback_sports') }}">
    @if($label)
        <span>{{ $label }}</span>
    @endif
    <h3>{{ $title }}</h3>
    @if($description)
        <p>{{ $description }}</p>
    @endif
    @if($href && ! $disabled)
        <a href="{{ $href }}">Open</a>
    @elseif($label)
        <em>{{ $label }}</em>
    @endif
</article>

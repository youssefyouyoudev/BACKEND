@props(['title', 'description' => null, 'href' => null, 'label' => null, 'image' => null, 'disabled' => false])

<article {{ $attributes->merge(['class' => 'rm-media-card'.($disabled ? ' is-disabled' : '')]) }}>
    @if($image)
        <img src="{{ $image }}" alt="" loading="lazy">
    @endif
    @if($label)
        <span>{{ $label }}</span>
    @endif
    <h3>{{ $title }}</h3>
    @if($description)
        <p>{{ $description }}</p>
    @endif
    @if($href && ! $disabled)
        <a href="{{ $href }}">Open</a>
    @else
        <em>Coming soon</em>
    @endif
</article>

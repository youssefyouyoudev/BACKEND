@props(['eyebrow' => null, 'title', 'description' => null])

<section {{ $attributes->merge(['class' => 'rm-page-hero']) }}>
    @if($eyebrow)
        <span class="rm-kicker">{{ $eyebrow }}</span>
    @endif
    <h1>{{ $title }}</h1>
    @if($description)
        <p>{{ $description }}</p>
    @endif
    {{ $slot }}
</section>

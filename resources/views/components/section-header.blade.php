@props(['eyebrow' => null, 'title', 'description' => null, 'href' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'rm-section-header']) }}>
    <div>
        @if($eyebrow)
            <p class="rm-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2>{{ $title }}</h2>
        @if($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if($href && $action)
        <a href="{{ $href }}" class="rm-section-header__link">{{ $action }}</a>
    @endif
</div>

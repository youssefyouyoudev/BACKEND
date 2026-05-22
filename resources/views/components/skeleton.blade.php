@props(['count' => 3])

<div {{ $attributes->merge(['class' => 'rm-skeleton-grid']) }} aria-label="Loading">
    @for($i = 0; $i < $count; $i++)
        <span class="rm-skeleton-card"></span>
    @endfor
</div>

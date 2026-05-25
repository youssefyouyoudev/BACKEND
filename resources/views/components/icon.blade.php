@props(['name', 'class' => 'rm-icon'])

@php
    $icons = [
        'tv' => '<rect x="3" y="5" width="18" height="12" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path>',
        'football' => '<circle cx="12" cy="12" r="9"></circle><path d="m9 9 3-2 3 2-1 4h-4L9 9Z"></path><path d="m10 13-2 3"></path><path d="m14 13 2 3"></path><path d="M9 9 6 8"></path><path d="m15 9 3-1"></path>',
        'scores' => '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path><path d="M8 6v12"></path><path d="M16 6v12"></path>',
        'calendar' => '<path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18"></path>',
        'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'play' => '<circle cx="12" cy="12" r="10"></circle><path d="m10 8 6 4-6 4V8Z"></path>',
        'star' => '<path d="m12 2 2.9 6 6.6.9-4.8 4.7 1.1 6.6L12 17.1l-5.8 3.1 1.1-6.6-4.8-4.7 6.6-.9L12 2Z"></path>',
        'trending' => '<path d="m22 7-8.5 8.5-5-5L2 17"></path><path d="M16 7h6v6"></path>',
        'trophy' => '<path d="M8 21h8"></path><path d="M12 17v4"></path><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"></path><path d="M5 5H3v2a4 4 0 0 0 4 4"></path><path d="M19 5h2v2a4 4 0 0 1-4 4"></path>',
        'globe' => '<circle cx="12" cy="12" r="10"></circle><path d="M2 12h20"></path><path d="M12 2a15 15 0 0 1 0 20"></path><path d="M12 2a15 15 0 0 0 0 20"></path>',
        'signal' => '<path d="M2 20h.01"></path><path d="M7 20a5 5 0 0 0-5-5"></path><path d="M12 20A10 10 0 0 0 2 10"></path><path d="M17 20A15 15 0 0 0 2 5"></path>',
        'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>',
        'menu' => '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path>',
        'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M20 21a8 8 0 0 0-16 0"></path>',
        'news' => '<path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20V4H6.5A2.5 2.5 0 0 0 4 6.5v13Z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h5"></path>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"></path>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'aria-hidden' => 'true']) }} stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    {!! $icons[$name] ?? $icons['star'] !!}
</svg>

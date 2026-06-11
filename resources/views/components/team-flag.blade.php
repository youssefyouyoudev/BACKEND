@props([
    'team',
    'src' => null,
    'size' => 'md',
    'loading' => 'lazy',
])

@php($flagUrl = \App\Support\TeamFlag::url($team, $src))

@if($flagUrl)
    <span {{ $attributes->class(['team-flag', "team-flag--{$size}"]) }}>
        <img src="{{ $flagUrl }}" alt="{{ $team }} flag" loading="{{ $loading }}" decoding="async">
    </span>
@endif

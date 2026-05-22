@props(['action' => null, 'placeholder' => 'Search RifiMedia', 'value' => null])

<form {{ $attributes->merge(['class' => 'rm-search rm-search--wide']) }} action="{{ $action ?: route('search') }}" method="GET">
    <input type="search" name="q" value="{{ $value ?? request('q') }}" placeholder="{{ $placeholder }}">
    <button class="rm-btn rm-btn-primary" type="submit">Search</button>
</form>

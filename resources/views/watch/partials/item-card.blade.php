<a href="{{ route('watch.item', $item) }}" class="iptv-item-card" data-focusable>
    <span class="iptv-item-card__poster">
        @if($item->logo)
            <img src="{{ $item->logo }}" alt="" loading="lazy">
        @else
            <strong>{{ str($item->name)->substr(0, 1)->upper() }}</strong>
        @endif
        @if($item->is_adult)
            <em>{{ __("Lock") }}</em>
        @endif
    </span>
    <span class="iptv-item-card__body">
        <strong>{{ $item->name }}</strong>
        <small>{{ str($item->type)->headline() }}{{ $item->category ? ' · '.$item->category->name : '' }}</small>
    </span>
</a>

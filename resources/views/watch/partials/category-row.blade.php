@if($categories->isNotEmpty())
    <section class="iptv-row">
        <div class="iptv-row__header">
            <h2>{{ $title }}</h2>
        </div>
        <div class="iptv-category-grid">
            @foreach($categories as $category)
                <a href="{{ route('watch.category', $category) }}" class="iptv-category-chip">
                    <span>{{ $category->name }}</span>
                    <small>{{ number_format($category->items_count) }} items</small>
                    @if(\App\Models\IptvItem::isAdultName($category->name))
                        <b aria-label="Locked">Lock</b>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif

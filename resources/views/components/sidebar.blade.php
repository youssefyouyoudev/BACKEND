@props(['channels' => [], 'activeId' => null])

<aside class="rm-related-panel" aria-label="Related live streams">
    <div class="rm-related-panel__header">
        <x-logo compact />
        <span>Related live</span>
    </div>

    <div class="rm-related-panel__list">
        @forelse($channels as $channel)
            @php
                $id = data_get($channel, 'id');
                $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: asset('brand/rifi-logo.png');
                $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'General';
                $tags = collect(data_get($channel, 'display_tags', []))->take(2);
            @endphp
            <a
                href="{{ route('channels.show', $id) }}"
                class="rm-related-channel {{ (int) $activeId === (int) $id ? 'is-active' : '' }}"
                data-channel-id="{{ $id }}"
            >
                <img src="{{ $logo }}" alt="" loading="lazy" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
                <span>
                    <strong>{{ data_get($channel, 'name') }}</strong>
                    <small>{{ $category }}@if($tags->isNotEmpty()) - {{ $tags->join(' / ') }}@endif</small>
                </span>
                <i></i>
            </a>
        @empty
            <div class="rm-empty-state rm-empty-state--compact">
                <span>No related streams</span>
                <strong>More channels will appear here once available.</strong>
            </div>
        @endforelse
    </div>
</aside>

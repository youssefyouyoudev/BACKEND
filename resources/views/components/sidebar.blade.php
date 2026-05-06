@props(['channels' => [], 'activeId' => null])

<aside class="sat-sidebar" aria-label="Channel rail">
    <div class="sat-sidebar__brand">
        <x-logo compact />
        <span>Live control</span>
    </div>
    <div class="sat-sidebar__scroll">
        @foreach($channels as $channel)
            @php
                $id = data_get($channel, 'id');
                $logo = data_get($channel, 'logo') ?: data_get($channel, 'thumbnail') ?: asset('brand/rifi-logo.png');
                $category = data_get($channel, 'category') ?: data_get($channel, 'group_title') ?: 'General';
            @endphp
            <a
                href="{{ route('channels.show', $id) }}"
                class="sat-sidebar-channel {{ (int) $activeId === (int) $id ? 'is-active' : '' }}"
                data-channel-id="{{ $id }}"
            >
                <img src="{{ $logo }}" alt="" loading="lazy" onerror="this.src='{{ asset('brand/rifi-logo.png') }}'">
                <span>
                    <strong>{{ data_get($channel, 'name') }}</strong>
                    <small>{{ $category }}</small>
                </span>
                <i></i>
            </a>
        @endforeach
    </div>
</aside>

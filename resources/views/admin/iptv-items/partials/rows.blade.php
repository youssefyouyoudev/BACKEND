@forelse($items as $item)
    <tr data-item-row="{{ $item->id }}" class="{{ $item->is_public ? '' : 'is-hidden-publicly' }}">
        <td>
            <div class="iptv-admin-item">
                <img src="{{ $item->logo ?: asset('brand/rifi-logo.png') }}" alt="" loading="lazy" data-fallback-src="{{ asset('brand/rifi-logo.png') }}">
                <span>
                    <strong>{{ $item->name }}</strong>
                    <small>{{ $item->tvg_id ?: 'No TVG ID' }}</small>
                </span>
            </div>
        </td>
        <td><span class="iptv-admin-type iptv-admin-type--{{ $item->type }}">{{ str($item->type)->headline() }}</span></td>
        <td>{{ $item->category?->name ?? $item->group_title ?? 'Uncategorized' }}</td>
        <td>{{ $item->playlist?->name ?? 'Deleted playlist' }}</td>
        <td>
            <span class="status-pill status-pill--{{ $item->is_active ? 'ready' : 'offline' }}">
                {{ $item->is_active ? 'Imported active' : 'Importer disabled' }}
            </span>
        </td>
        <td><span class="iptv-admin-type">{{ $item->qualityLabel() }}</span></td>
        <td>
            @php
                $playbackReady = $item->is_active && $item->is_public && filled($item->stream_url);
            @endphp
            <span class="status-pill status-pill--{{ $playbackReady ? 'ready' : 'offline' }}">
                {{ $playbackReady ? 'Ready' : ($item->stream_url ? 'Not public' : 'Missing source') }}
            </span>
        </td>
        <td>{{ filled($item->stream_url) ? 'Source available' : 'No source' }}</td>
        <td>
            <button
                type="button"
                class="iptv-public-toggle {{ $item->is_public ? 'is-public' : '' }}"
                data-visibility-toggle
                data-url="{{ route('admin.iptv-items.visibility', $item) }}"
                data-is-public="{{ $item->is_public ? '1' : '0' }}"
                aria-pressed="{{ $item->is_public ? 'true' : 'false' }}"
            >
                <span class="iptv-public-toggle__track"><i></i></span>
                <span data-toggle-label>{{ $item->is_public ? 'Public' : 'Hidden' }}</span>
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9">
            <div class="empty-state empty-state--compact">
                <h3>No IPTV items match these filters.</h3>
                <p>Try a broader search or reset the filters.</p>
            </div>
        </td>
    </tr>
@endforelse

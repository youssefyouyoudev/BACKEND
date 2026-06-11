@extends('layouts.admin')

@section('content')
    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ __("IPTV sources") }}</p>
                <h1>{{ __("Playlists") }}</h1>
            </div>
            <a href="{{ route('admin.playlists.create') }}" class="button button--primary">{{ __("Add Playlist") }}</a>
        </div>

        @if($playlists->isEmpty())
            <div class="empty-state">
                <h3>{{ __("No playlists yet.") }}</h3>
                <p>{{ __("Add your own M3U, Xtream Codes, active code, or uploaded M3U file.") }}</p>
            </div>
        @else
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __("Name") }}</th>
                            <th>{{ __("Type") }}</th>
                            <th>{{ __("Status") }}</th>
                            <th>{{ __("Imported") }}</th>
                            <th>{{ __("Last import") }}</th>
                            <th>{{ __("Source") }}</th>
                            <th class="text-end">{{ __("Actions") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playlists as $playlist)
                            <tr>
                                <td>
                                    <strong>{{ $playlist->name }}</strong>
                                    <span class="table-subtle">{{ $playlist->iptv_items_count }} IPTV items</span>
                                </td>
                                <td>{{ str($playlist->input_type)->replace('_', ' ')->headline() }}</td>
                                <td><span class="status-pill status-pill--{{ str($playlist->status)->slug('-') }}">{{ str($playlist->status)->headline() }}</span></td>
                                <td>
                                    <span class="table-subtle">Live: {{ number_format($playlist->imported_channels_count) }}</span>
                                    <span class="table-subtle">Movies: {{ number_format($playlist->imported_movies_count) }}</span>
                                    <span class="table-subtle">Series: {{ number_format($playlist->imported_series_count) }}</span>
                                </td>
                                <td>{{ $playlist->last_imported_at?->diffForHumans() ?? __('Never') }}</td>
                                <td>
                                    <span class="table-url">{{ $playlist->masked_m3u_url ?: ($playlist->server_url ? rtrim($playlist->server_url, '/').'/*' : ($playlist->original_filename ?: __('Not available'))) }}</span>
                                    @if($playlist->last_error)
                                        <span class="table-subtle">{{ $playlist->last_error }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.playlists.edit', $playlist) }}" class="button button--ghost">{{ __("Edit") }}</a>
                                    <form method="POST" action="{{ route('admin.playlists.reimport', $playlist) }}">
                                        @csrf
                                        <button type="submit" class="button button--ghost">{{ __("Reimport") }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.playlists.destroy', $playlist) }}" onsubmit="return confirm(@js(__('Delete this playlist and imported IPTV items?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button button--danger">{{ __("Delete") }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $playlists->links() }}
        @endif
    </section>
@endsection

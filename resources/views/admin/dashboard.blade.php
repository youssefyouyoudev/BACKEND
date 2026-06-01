@php
    $lastSync = $stats['last_sync'] ? \Illuminate\Support\Carbon::parse($stats['last_sync']) : null;
@endphp

@extends('layouts.admin')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-header__eyebrow">Admin Dashboard</p>
            <h1>Playlist ingestion and channel publishing.</h1>
            <p class="page-header__copy">
                Import legal M3U sources once, parse them asynchronously, and expose a fast channel browser to the public player.
            </p>
        </div>
        <div class="page-header__status-card">
            <span class="page-header__status-label">Last successful sync</span>
            <strong>{{ $lastSync ? $lastSync->diffForHumans() : 'No sync yet' }}</strong>
            <span class="page-header__status-note">{{ $lastSync?->format('M d, Y H:i') ?? 'Waiting for the first import.' }}</span>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-card__label">Playlists</span>
            <strong>{{ number_format($stats['playlists']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Channels</span>
            <strong>{{ number_format($stats['channels']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Categories</span>
            <strong>{{ number_format($stats['categories']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Queue mode</span>
            <strong>{{ strtoupper(config('queue.default')) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Online streams</span>
            <strong>{{ number_format($stats['online_streams']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Offline streams</span>
            <strong>{{ number_format($stats['offline_streams']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Unknown streams</span>
            <strong>{{ number_format($stats['unknown_streams']) }}</strong>
        </article>
    </section>

    <section class="admin-grid">
        <article class="surface-card" id="playlist-form">
            <div class="surface-card__header">
                <div>
                    <p class="surface-card__eyebrow">Add new source</p>
                    <h2>Register an M3U playlist</h2>
                </div>
                <span class="surface-card__badge">Safe URL validation enabled</span>
            </div>

            <form method="POST" action="{{ route('admin.playlists.store') }}" class="form-card form-card--embedded" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label for="name">Playlist name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Premium Sports Feed">
                </div>

                <div class="field">
                    <label for="m3u_url">M3U URL</label>
                    <input id="m3u_url" type="url" name="m3u_url" value="{{ old('m3u_url') }}" placeholder="https://partner.example.com/channel-pack.m3u">
                </div>

                <div class="field">
                    <label for="playlist_file">Upload M3U file</label>
                    <input id="playlist_file" type="file" name="playlist_file" accept=".m3u,.m3u8,.txt">
                    <small class="field__hint">Choose either a remote URL or an uploaded playlist file.</small>
                </div>

                <div class="legal-callout">
                    <strong>Legal reminder</strong>
                    <p>{{ $appSettings['legal_notice'] }}</p>
                </div>

                <button type="submit" class="button button--primary" id="save-playlist-btn">Save &amp; Import</button>
            </form>
        </article>

        <article class="surface-card surface-card--accent">
            <div class="surface-card__header">
                <div>
                    <p class="surface-card__eyebrow">Operations</p>
                    <h2>How parsing works</h2>
                </div>
            </div>
            <ol class="check-list">
                <li>Admin adds a legal playlist URL or uploads an M3U file.</li>
                <li>The parse action runs immediately in `sync` mode or queues a background job.</li>
                <li>The parser extracts channel names, logos, categories, and stream URLs.</li>
                <li>Old channels are cleared and the playlist sync timestamp is refreshed after every import.</li>
            </ol>
        </article>
    </section>

    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">Stream health</p>
                <h2>Failed sources</h2>
            </div>
            <span class="surface-card__badge">Checked by scheduler</span>
        </div>

        @if($failedSources->isEmpty())
            <div class="empty-state empty-state--compact">
                <h3>No failed stream sources recorded.</h3>
                <p>Run <code>php artisan streams:check-health</code> to populate health status.</p>
            </div>
        @else
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>HTTP</th>
                            <th>Latency</th>
                            <th>Last checked</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedSources as $source)
                            <tr>
                                <td>
                                    <strong>{{ $source->channel?->clean_display_name ?? 'Unknown channel' }}</strong>
                                    <span class="table-subtle">{{ $source->label ?: 'Server '.$source->priority }}</span>
                                </td>
                                <td><span class="status-pill status-pill--{{ str($source->health_status)->slug('-') }}">{{ str($source->health_status)->headline() }}</span></td>
                                <td>{{ $source->response_code ?: '-' }}</td>
                                <td>{{ $source->latency_ms ? $source->latency_ms.' ms' : '-' }}</td>
                                <td>{{ $source->last_checked_at?->diffForHumans() ?? 'Never' }}</td>
                                <td><span class="table-url">{{ $source->last_error ?: 'No details' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="surface-card" id="playlist-table">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">Stored catalogs</p>
                <h2>Playlists and parsing status</h2>
            </div>
        </div>

        @if ($playlists->count() === 0)
            <div class="empty-state">
                <h3>No playlists yet.</h3>
                <p>Add your first M3U source above to start building the channel library.</p>
            </div>
        @else
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Channels</th>
                            <th>Groups</th>
                            <th>Last Sync</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($playlists as $playlist)
                            <tr>
                                <td>
                                    <strong>{{ $playlist->name }}</strong>
                                    <span class="table-subtle">Added {{ $playlist->created_at?->diffForHumans() }}</span>
                                </td>
                                <td>
                                    @if ($playlist->source_type === \App\Models\Playlist::SOURCE_TYPE_FILE)
                                        <span class="table-subtle">Uploaded file</span>
                                        <span class="table-url">{{ $playlist->original_filename ?: $playlist->file_path }}</span>
                                    @else
                                        <span class="table-subtle">Remote URL</span>
                                        <span class="table-url">{{ $playlist->source_url }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-pill status-pill--{{ str($playlist->status)->slug('-') }}">
                                        {{ str($playlist->status)->headline() }}
                                    </span>
                                </td>
                                <td>{{ number_format($playlist->channels_count) }}</td>
                                <td>{{ count($playlist->import_summary['groups'] ?? []) }}</td>
                                <td>
                                    <strong>{{ $playlist->last_synced_at?->diffForHumans() ?? 'Never' }}</strong>
                                    <span class="table-subtle">{{ $playlist->last_synced_at?->format('M d, Y H:i') ?? 'Not parsed yet' }}</span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.playlists.parse', $playlist) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="button button--ghost"
                                            @disabled(in_array($playlist->status, ['queued', 'processing'], true))
                                        >
                                            {{ in_array($playlist->status, ['queued', 'processing'], true) ? 'Parsing…' : 'Re-parse' }}
                                        </button>
                                    </form>

                                    <details class="playlist-editor">
                                        <summary class="button button--ghost">Edit</summary>
                                        <form method="POST" action="{{ route('admin.playlists.update', $playlist) }}" class="playlist-editor__form" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')

                                            <div class="field">
                                                <label for="playlist-{{ $playlist->id }}-name">Playlist name</label>
                                                <input id="playlist-{{ $playlist->id }}-name" type="text" name="name" value="{{ old('name', $playlist->name) }}" required maxlength="120">
                                            </div>

                                            <div class="field">
                                                <label for="playlist-{{ $playlist->id }}-url">M3U URL</label>
                                                <input
                                                    id="playlist-{{ $playlist->id }}-url"
                                                    type="url"
                                                    name="m3u_url"
                                                    value="{{ old('m3u_url', $playlist->source_type === \App\Models\Playlist::SOURCE_TYPE_URL ? $playlist->source_url : '') }}"
                                                    placeholder="https://partner.example.com/channel-pack.m3u"
                                                >
                                                <small class="field__hint">Leave empty when uploading a replacement file.</small>
                                            </div>

                                            <div class="field">
                                                <label for="playlist-{{ $playlist->id }}-file">Replace with file</label>
                                                <input id="playlist-{{ $playlist->id }}-file" type="file" name="playlist_file" accept=".m3u,.m3u8,.txt">
                                                <small class="field__hint">Saving changes runs a fresh parse immediately.</small>
                                            </div>

                                                <button type="submit" class="button button--primary">Save &amp; Re-parse</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('admin.playlists.destroy', $playlist) }}" onsubmit="return confirm('Delete this playlist and all imported channels?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button button--danger">Delete</button>
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

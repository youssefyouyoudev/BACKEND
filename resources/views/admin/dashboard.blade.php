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
        <article class="stat-card">
            <span class="stat-card__label">World Cup matches</span>
            <strong>{{ number_format($stats['world_cup_matches']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Matches with channel</span>
            <strong>{{ number_format($stats['world_cup_with_channel']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Missing channel</span>
            <strong>{{ number_format($stats['world_cup_missing_channel']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Missing commentator</span>
            <strong>{{ number_format($stats['world_cup_missing_commentator']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Live links enabled</span>
            <strong>{{ number_format($stats['world_cup_live_enabled']) }}</strong>
        </article>
    </section>

    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">World Cup 2026</p>
                <h2>Next group-stage matches</h2>
            </div>
            <a class="button button--primary" href="{{ route('admin.world-cup-matches.index') }}">Manage matches</a>
        </div>
        <div class="wc-dashboard-list">
            @forelse($nextWorldCupMatches as $match)
                <a href="{{ route('admin.world-cup-matches.edit', $match) }}">
                    <span>{{ $match->group_name }}</span>
                    <strong>{{ $match->home_team }} vs {{ $match->away_team }}</strong>
                    <small>{{ $match->morocco_kickoff_at?->format('M d, H:i') }} Morocco · {{ $match->public_channel_name }}</small>
                </a>
            @empty
                <p>No upcoming matches are currently stored.</p>
            @endforelse
        </div>
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
                @php($selectedInputType = old('input_type', \App\Models\Playlist::INPUT_TYPE_REMOTE_URL))

                <div class="field">
                    <label for="input_type">Playlist Input Type</label>
                    <select id="input_type" name="input_type" required data-playlist-input-type>
                        <option value="{{ \App\Models\Playlist::INPUT_TYPE_M3U_URL }}" @selected($selectedInputType === \App\Models\Playlist::INPUT_TYPE_M3U_URL)>M3U URL</option>
                        <option value="{{ \App\Models\Playlist::INPUT_TYPE_XTREAM }}" @selected($selectedInputType === \App\Models\Playlist::INPUT_TYPE_XTREAM)>Xtream Codes</option>
                        <option value="{{ \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE }}" @selected($selectedInputType === \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE)>Active Code</option>
                        <option value="{{ \App\Models\Playlist::INPUT_TYPE_UPLOAD }}" @selected($selectedInputType === \App\Models\Playlist::INPUT_TYPE_UPLOAD)>Upload M3U File</option>
                    </select>
                </div>

                <div class="field">
                    <label for="name">Playlist name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Premium Sports Feed">
                </div>

                <div class="field" data-playlist-field="m3u_url active_code">
                    <label for="m3u_url">M3U URL</label>
                    <input id="m3u_url" type="url" name="m3u_url" value="{{ old('m3u_url') }}" placeholder="https://partner.example.com/channel-pack.m3u">
                    <small class="field__hint">
                        Examples: http://example.com/playlist.m3u<br>
                        http://example.com/get.php?username=USER&amp;password=PASS&amp;type=m3u_plus&amp;output=mpegts<br>
                        http://example.com/in/gets.php?user=USER&amp;pass=PASS&amp;t=m3uplus&amp;o=mpegts
                    </small>
                </div>

                <div class="field" data-playlist-field="upload">
                    <label for="playlist_file">Upload M3U file</label>
                    <input id="playlist_file" type="file" name="playlist_file" accept=".m3u,.m3u8,.txt">
                    <small class="field__hint">Accepted formats: .m3u, .m3u8, .txt. Maximum size: 10 MB.</small>
                </div>

                <div class="field" data-playlist-field="xtream">
                    <label for="server_url">Server URL</label>
                    <input id="server_url" type="url" name="server_url" value="{{ old('server_url') }}" placeholder="http://domain.com">
                </div>

                <div class="field" data-playlist-field="xtream">
                    <label for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" autocomplete="off">
                </div>

                <div class="field" data-playlist-field="xtream">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" autocomplete="new-password">
                </div>

                <div class="field" data-playlist-field="xtream">
                    <label for="output">Output</label>
                    <select id="output" name="output">
                        <option value="mpegts" @selected(old('output', 'mpegts') === 'mpegts')>mpegts</option>
                        <option value="hls" @selected(old('output') === 'hls')>hls</option>
                    </select>
                    <small class="field__hint" data-xtream-preview></small>
                </div>

                <div class="field" data-playlist-field="active_code">
                    <label for="active_code">Active Code</label>
                    <input id="active_code" type="text" name="active_code" value="{{ old('active_code') }}" minlength="4" maxlength="64" pattern="[A-Za-z0-9]+" autocomplete="off" placeholder="696966207988">
                    <small class="field__hint">Use this when your provider gives you an activation code with or without an M3U link.</small>
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
                                    @if ($playlist->input_type === \App\Models\Playlist::INPUT_TYPE_UPLOAD_FILE)
                                        <span class="table-subtle">Uploaded file</span>
                                        <span class="table-url">{{ $playlist->original_filename ?: $playlist->file_path }}</span>
                                    @elseif ($playlist->input_type === \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE)
                                        <span class="table-subtle">Active Code</span>
                                        <span class="table-url">{{ $playlist->masked_m3u_url ?: 'M3U URL needed before parsing' }}</span>
                                    @else
                                        <span class="table-subtle">Remote URL</span>
                                        <span class="table-url">{{ $playlist->masked_m3u_url }}</span>
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
                                            @disabled(in_array($playlist->status, ['queued', 'processing'], true) || ($playlist->input_type === \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE && blank($playlist->m3u_url)))
                                        >
                                            {{ in_array($playlist->status, ['queued', 'processing'], true) ? 'Parsing…' : 'Re-parse' }}
                                        </button>
                                    </form>

                                    <details class="playlist-editor">
                                        <summary class="button button--ghost">Edit</summary>
                                        <form method="POST" action="{{ route('admin.playlists.update', $playlist) }}" class="playlist-editor__form" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            @php($editInputType = old('input_type', $playlist->input_type))

                                            <div class="field">
                                                <label for="playlist-{{ $playlist->id }}-input-type">Playlist Input Type</label>
                                                <select id="playlist-{{ $playlist->id }}-input-type" name="input_type" required data-playlist-input-type>
                                                    <option value="{{ \App\Models\Playlist::INPUT_TYPE_M3U_URL }}" @selected($editInputType === \App\Models\Playlist::INPUT_TYPE_M3U_URL)>M3U URL</option>
                                                    <option value="{{ \App\Models\Playlist::INPUT_TYPE_XTREAM }}" @selected($editInputType === \App\Models\Playlist::INPUT_TYPE_XTREAM)>Xtream Codes</option>
                                                    <option value="{{ \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE }}" @selected($editInputType === \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE)>Active Code</option>
                                                    <option value="{{ \App\Models\Playlist::INPUT_TYPE_UPLOAD }}" @selected($editInputType === \App\Models\Playlist::INPUT_TYPE_UPLOAD)>Upload M3U File</option>
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label for="playlist-{{ $playlist->id }}-name">Playlist name</label>
                                                <input id="playlist-{{ $playlist->id }}-name" type="text" name="name" value="{{ old('name', $playlist->name) }}" required maxlength="120">
                                            </div>

                                            <div class="field" data-playlist-field="m3u_url active_code">
                                                <label for="playlist-{{ $playlist->id }}-url">M3U URL</label>
                                                <input
                                                    id="playlist-{{ $playlist->id }}-url"
                                                    type="url"
                                                    name="m3u_url"
                                                    value="{{ old('m3u_url', $playlist->source_type === \App\Models\Playlist::SOURCE_TYPE_URL ? $playlist->m3u_url : '') }}"
                                                    placeholder="https://partner.example.com/channel-pack.m3u"
                                                >
                                                <small class="field__hint">Remote mode requires a URL. Active Code mode can save without one.</small>
                                            </div>

                                            <div class="field" data-playlist-field="upload">
                                                <label for="playlist-{{ $playlist->id }}-file">Replace with file</label>
                                                <input id="playlist-{{ $playlist->id }}-file" type="file" name="playlist_file" accept=".m3u,.m3u8,.txt" @if($playlist->resolved_file_path) data-has-existing-file="1" @endif>
                                                <small class="field__hint">Saving changes runs a fresh parse immediately.</small>
                                            </div>

                                            <div class="field" data-playlist-field="xtream">
                                                <label for="playlist-{{ $playlist->id }}-server-url">Server URL</label>
                                                <input id="playlist-{{ $playlist->id }}-server-url" type="url" name="server_url" value="{{ old('server_url', $playlist->server_url) }}" placeholder="http://domain.com">
                                            </div>

                                            <div class="field" data-playlist-field="xtream">
                                                <label for="playlist-{{ $playlist->id }}-username">Username</label>
                                                <input id="playlist-{{ $playlist->id }}-username" name="username" value="{{ old('username', $playlist->username) }}" autocomplete="off">
                                            </div>

                                            <div class="field" data-playlist-field="xtream">
                                                <label for="playlist-{{ $playlist->id }}-password">Password</label>
                                                <input id="playlist-{{ $playlist->id }}-password" type="password" name="password" autocomplete="new-password" placeholder="{{ $playlist->password ? 'Leave unchanged' : '' }}">
                                            </div>

                                            <div class="field" data-playlist-field="xtream">
                                                <label for="playlist-{{ $playlist->id }}-output">Output</label>
                                                <select id="playlist-{{ $playlist->id }}-output" name="output">
                                                    <option value="mpegts" @selected(old('output', $playlist->output ?? 'mpegts') === 'mpegts')>mpegts</option>
                                                    <option value="hls" @selected(old('output', $playlist->output ?? 'mpegts') === 'hls')>hls</option>
                                                </select>
                                                <small class="field__hint" data-xtream-preview></small>
                                            </div>

                                            <div class="field" data-playlist-field="active_code">
                                                <label for="playlist-{{ $playlist->id }}-active-code">Active Code</label>
                                                <input id="playlist-{{ $playlist->id }}-active-code" type="text" name="active_code" value="{{ old('active_code') }}" minlength="4" maxlength="64" pattern="[A-Za-z0-9]+" autocomplete="off" placeholder="{{ $playlist->active_code ? 'Leave unchanged' : '696966207988' }}">
                                                <small class="field__hint">Use this when your provider gives you an activation code with or without an M3U link.</small>
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

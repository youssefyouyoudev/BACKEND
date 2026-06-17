@php
    $inputType = old('input_type', $playlist->input_type ?: \App\Models\Playlist::INPUT_TYPE_M3U_URL);
@endphp

<form method="POST" action="{{ $action }}" class="form-card" enctype="multipart/form-data">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="field">
        <label for="input_type">{{ __("Playlist Input Type") }}</label>
        <select id="input_type" name="input_type" required data-playlist-input-type>
            <option value="{{ \App\Models\Playlist::INPUT_TYPE_M3U_URL }}" @selected($inputType === \App\Models\Playlist::INPUT_TYPE_M3U_URL)>{{ __("M3U URL") }}</option>
            <option value="{{ \App\Models\Playlist::INPUT_TYPE_XTREAM }}" @selected($inputType === \App\Models\Playlist::INPUT_TYPE_XTREAM)>{{ __("Xtream Codes") }}</option>
            <option value="{{ \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE }}" @selected($inputType === \App\Models\Playlist::INPUT_TYPE_ACTIVE_CODE)>{{ __("Active Code") }}</option>
            <option value="{{ \App\Models\Playlist::INPUT_TYPE_UPLOAD }}" @selected($inputType === \App\Models\Playlist::INPUT_TYPE_UPLOAD)>{{ __("Upload M3U File") }}</option>
        </select>
    </div>

    <div class="field">
        <label for="name">{{ __("Playlist name") }}</label>
        <input id="name" type="text" name="name" value="{{ old('name', $playlist->name) }}" required maxlength="120" placeholder="{{ __("My IPTV Pack") }}">
    </div>

    <div class="field" data-playlist-field="m3u_url active_code">
        <label for="m3u_url">{{ __("M3U URL") }}</label>
        <input id="m3u_url" type="url" name="m3u_url" value="{{ old('m3u_url', $playlist->m3u_url) }}" placeholder="http://domain.com/get.php?username=USER&amp;password=PASS&amp;type=m3u_plus&amp;output=mpegts">
        <small class="field__hint">{{ __("Supports .m3u, .m3u8, get.php URLs, and provider URLs with query strings.") }}</small>
    </div>

    <div class="field" data-playlist-field="xtream">
        <label for="server_url">{{ __("Server URL") }}</label>
        <input id="server_url" type="url" name="server_url" value="{{ old('server_url', $playlist->server_url) }}" placeholder="http://domain.com">
    </div>

    <div class="field" data-playlist-field="xtream">
        <label for="username">{{ __("Username") }}</label>
        <input id="username" name="username" value="{{ old('username', $playlist->username) }}" autocomplete="off">
    </div>

    <div class="field" data-playlist-field="xtream">
        <label for="password">{{ __("Password") }}</label>
        <input id="password" type="password" name="password" value="{{ old('password') }}" autocomplete="new-password" placeholder="{{ $playlist->exists && $playlist->password ? __('Leave unchanged') : '' }}">
    </div>

    <div class="field" data-playlist-field="xtream">
        <label for="output">{{ __("Output") }}</label>
        <select id="output" name="output">
            <option value="auto" @selected(old('output', $playlist->output ?? 'auto') === 'auto')>{{ __("Auto") }}</option>
            <option value="mpegts" @selected(old('output', $playlist->output ?? 'mpegts') === 'mpegts')>{{ __("mpegts") }}</option>
            <option value="hls" @selected(old('output', $playlist->output ?? 'mpegts') === 'hls')>{{ __("hls") }}</option>
        </select>
        <small class="field__hint" data-xtream-preview></small>
    </div>

    <div class="field" data-playlist-field="active_code">
        <label for="active_code">{{ __("Active Code") }}</label>
        <input id="active_code" name="active_code" value="{{ old('active_code') }}" minlength="4" maxlength="64" pattern="[A-Za-z0-9]+" autocomplete="off" placeholder="{{ $playlist->exists && $playlist->active_code ? __('Leave unchanged') : '696966207988' }}">
        <small class="field__hint">{{ __("Use this when your provider gives you an activation code with or without an M3U link.") }}</small>
    </div>

    <div class="field" data-playlist-field="upload">
        <label for="playlist_file">{{ __("Upload M3U file") }}</label>
        <input id="playlist_file" type="file" name="playlist_file" accept=".m3u,.m3u8,.txt" @if($playlist->resolved_file_path) data-has-existing-file="1" @endif>
        <small class="field__hint">{{ __("Accepted formats: .m3u, .m3u8, .txt. Maximum size: 10 MB.") }}</small>
    </div>

    <button type="submit" class="button button--primary">{{ $button }}</button>
</form>

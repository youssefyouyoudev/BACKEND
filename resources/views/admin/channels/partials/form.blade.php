<div class="form-grid">
    <div class="field">
        <label for="name">Channel name</label>
        <input id="name" name="name" value="{{ old('name', $channel->name ?? '') }}" required maxlength="140" placeholder="RiFi Sports 1">
    </div>
    <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $channel->slug ?? '') }}" maxlength="160" placeholder="rifi-sports-1">
    </div>
    <div class="field">
        <label for="playlist_id">Playlist</label>
        <select id="playlist_id" name="playlist_id" required>
            @foreach($playlists as $playlist)
                <option value="{{ $playlist->id }}" @selected((int) old('playlist_id', $channel->playlist_id ?? '') === $playlist->id)>{{ $playlist->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">General</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $channel->category_id ?? '') === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="logo">Logo URL</label>
        <input id="logo" name="logo" value="{{ old('logo', $channel->logo ?? '') }}" placeholder="https://example.com/logo.png">
    </div>
    <div class="field">
        <label for="stream_type">Stream type</label>
        <select id="stream_type" name="stream_type">
            @foreach(['hls' => 'HLS / m3u8', 'dash' => 'MPEG-DASH', 'mp4' => 'MP4', 'mpegts' => 'MPEG-TS', 'stream' => 'Generic'] as $value => $label)
                <option value="{{ $value }}" @selected(old('stream_type', $channel->stream_type ?? 'hls') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field form-grid__wide">
        <label for="stream_url">Primary stream URL</label>
        <input id="stream_url" type="url" name="stream_url" value="{{ old('stream_url', $channel->stream_url ?? '') }}" required placeholder="https://cdn.example.com/live/master.m3u8">
    </div>
    <div class="field">
        <label for="featured_rank">Featured rank</label>
        <input id="featured_rank" type="number" min="1" name="featured_rank" value="{{ old('featured_rank', $channel->featured_rank ?? '') }}">
    </div>
    <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $channel->sort_order ?? '') }}">
    </div>
</div>

<div class="toggle-row">
    <label class="checkbox-field"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $channel->is_active ?? true))><span>Active</span></label>
    <label class="checkbox-field"><input type="checkbox" name="is_live" value="1" @checked(old('is_live', $channel->is_live ?? true))><span>Live</span></label>
    <label class="checkbox-field"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $channel->is_featured ?? false))><span>Featured</span></label>
</div>

@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("Channel management") }}</p>
        <h1>{{ $item->name }}</h1>
        <p class="page-header__copy">{{ $item->playlist?->name }} &middot; {{ $item->category?->name ?? __('Uncategorized') }}</p>
    </div>
    <a href="{{ route('admin.iptv-items.index') }}" class="button button--ghost">{{ __("Back") }}</a>
</section>

<div class="iptv-source-admin-grid">
    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ __("Public channel details") }}</p>
                <h2>{{ __("Channel metadata") }}</h2>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.iptv-items.update', $item) }}" class="form-card">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <label class="field form-grid__wide"><span>{{ __("Name") }}</span><input name="name" value="{{ old('name', $item->name) }}" required maxlength="255"></label>
                <label class="field form-grid__wide"><span>{{ __("Logo URL") }}</span><input type="url" name="logo" value="{{ old('logo', $item->logo) }}"></label>
                <label class="field">
                    <span>{{ __("Category") }}</span>
                    <select name="category_id">
                        <option value="">{{ __("Uncategorized") }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $item->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>{{ __("TVG ID") }}</span><input name="tvg_id" value="{{ old('tvg_id', $item->tvg_id) }}"></label>
                <label class="field"><span>{{ __("TVG name") }}</span><input name="tvg_name" value="{{ old('tvg_name', $item->tvg_name) }}"></label>
                <label class="field"><span>{{ __("Language") }}</span><input name="language" value="{{ old('language', $item->language) }}"></label>
                <label class="field"><span>{{ __("Country code") }}</span><input name="country" value="{{ old('country', $item->country) }}" maxlength="8"></label>
                <label class="field">
                    <span>{{ __("Quality") }}</span>
                    <select name="quality_label">@foreach(['Auto', 'SD', 'HD', 'FHD', '4K'] as $quality)<option value="{{ $quality }}" @selected(old('quality_label', $item->quality_label) === $quality)>{{ $quality }}</option>@endforeach</select>
                </label>
                <label class="field">
                    <span>{{ __("Stream type") }}</span>
                    <select name="stream_type">@foreach(['auto', 'hls', 'mpegts', 'mp4'] as $type)<option value="{{ $type }}" @selected(old('stream_type', $item->stream_type) === $type)>{{ str($type)->upper() }}</option>@endforeach</select>
                </label>
            </div>
            <div class="iptv-admin-checks">
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> {{ __("Active") }}</label>
                <label><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $item->is_public))> {{ __("Public") }}</label>
                <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured))> {{ __("Featured") }}</label>
            </div>
            <button class="button button--primary">{{ __("Save channel") }}</button>
        </form>
    </section>

    <section class="surface-card">
        <div class="surface-card__header"><div><p class="surface-card__eyebrow">{{ __("Protected playback") }}</p><h2>{{ __("Source priority") }}</h2></div></div>
        <div class="iptv-source-list">
            @forelse($item->sources as $source)
                <article>
                    <form method="POST" action="{{ route('admin.iptv-items.sources.update', [$item, $source]) }}" class="iptv-source-priority-form">
                        @csrf
                        @method('PUT')
                        <input name="label" value="{{ $source->label }}" aria-label="{{ __('Source label') }}" required>
                        <select name="quality_label" aria-label="{{ __('Quality') }}">@foreach(['Auto', 'SD', 'HD', 'FHD', '4K'] as $quality)<option value="{{ $quality }}" @selected($source->quality_label === $quality)>{{ $quality }}</option>@endforeach</select>
                        <select name="type" aria-label="{{ __('Stream type') }}">@foreach(['auto', 'hls', 'mpegts', 'mp4'] as $type)<option value="{{ $type }}" @selected($source->type === $type)>{{ str($type)->upper() }}</option>@endforeach</select>
                        <input type="number" name="priority" value="{{ $source->priority }}" min="1" max="999" aria-label="{{ __('Priority') }}" required>
                        <label><input type="checkbox" name="is_active" value="1" @checked($source->is_active)> {{ __('Active') }}</label>
                        <small>{{ str($source->health_status)->headline() }}{{ $source->latency_ms ? ' - '.$source->latency_ms.' ms' : '' }}</small>
                        <button class="button button--ghost">{{ __("Update") }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.iptv-items.sources.test', [$item, $source]) }}">@csrf<button class="button button--ghost">{{ __("Test") }}</button></form>
                    <form method="POST" action="{{ route('admin.iptv-items.sources.destroy', [$item, $source]) }}">@csrf @method('DELETE')<button class="button button--danger">{{ __("Remove") }}</button></form>
                </article>
            @empty
                <p class="empty-state">{{ __("The imported source remains available. Add a source below to enable ordered backups.") }}</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.iptv-items.sources.store', $item) }}" class="form-card">
            @csrf
            <div class="form-grid">
                <label class="field"><span>{{ __("Label") }}</span><input name="label" value="{{ old('label', 'Backup') }}" required></label>
                <label class="field"><span>{{ __("Priority") }}</span><input type="number" name="priority" value="{{ old('priority', $item->sources->count() + 1) }}" min="1" max="999" required></label>
                <label class="field form-grid__wide"><span>{{ __("Authorized source URL") }}</span><input type="url" name="url" required></label>
                <label class="field"><span>{{ __("Type") }}</span><select name="type">@foreach(['auto', 'hls', 'mpegts', 'mp4'] as $type)<option value="{{ $type }}">{{ str($type)->upper() }}</option>@endforeach</select></label>
                <label class="field"><span>{{ __("Quality") }}</span><select name="quality_label">@foreach(['Auto', 'SD', 'HD', 'FHD', '4K'] as $quality)<option value="{{ $quality }}">{{ $quality }}</option>@endforeach</select></label>
            </div>
            <input type="hidden" name="is_active" value="1">
            <button class="button button--primary">{{ __("Add backup source") }}</button>
        </form>
    </section>
</div>
@endsection

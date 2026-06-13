@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div><p class="page-header__eyebrow">{{ $worldCupMatch->group_name }} · Match {{ $worldCupMatch->match_number }}</p><h1>{{ $worldCupMatch->home_team }} vs {{ $worldCupMatch->away_team }}.</h1></div>
    <a class="button button--ghost" href="{{ route('world-cup.index', ['search' => $worldCupMatch->home_team, 'tab' => 'all']) }}">{{ __("Public preview") }}</a>
</section>
<form method="POST" action="{{ route('admin.world-cup-matches.update', $worldCupMatch) }}">
    @csrf
    @method('PUT')
    @include('admin.world-cup-matches.partials.form', ['submitLabel' => 'Save match'])
</form>
@if($worldCupMatch->iptvItems->isNotEmpty())
    <section class="surface-card">
        <div class="surface-card__header">
            <div><p class="surface-card__eyebrow">{{ __("Watch Links / Channels") }}</p><h2>{{ __("Assigned IPTV channels and servers") }}</h2></div>
        </div>
        <div class="wc-admin-stream-list">
            @foreach($worldCupMatch->iptvItems->sortBy(fn ($item) => $item->pivot->priority) as $item)
                <form method="POST" action="{{ route('admin.world-cup-matches.update-iptv-item', [$worldCupMatch, $item]) }}" class="wc-admin-stream-row">
                    @csrf
                    @method('PATCH')
                    <div>
                        <strong>{{ $item->name }}</strong>
                        <small>{{ $item->qualityLabel() }} · {{ $item->category?->name ?: $item->group_title ?: __('General') }}</small>
                    </div>
                    <div class="wc-admin-stream-fields">
                        <div class="field"><label>{{ __("Channel name") }}</label><input name="channel_name" maxlength="160" value="{{ $item->pivot->channel_name }}" placeholder="{{ $item->name }}"></div>
                        <div class="field"><label>{{ __("Stream title") }}</label><input name="stream_title" maxlength="160" value="{{ $item->pivot->stream_title }}" placeholder="{{ $item->name }}"></div>
                        <div class="field"><label>{{ __("Server label") }}</label><input name="server_label" maxlength="80" value="{{ $item->pivot->server_label }}" placeholder="{{ __("Server 1") }}"></div>
                        <div class="field"><label>{{ __("Stream type") }}</label><select name="stream_type"><option value="">{{ __("From IPTV item") }}</option>@foreach(['hls', 'm3u8', 'mpegts', 'stream', 'mp4', 'iframe', 'external', 'channel_proxy'] as $type)<option value="{{ $type }}" @selected($item->pivot->stream_type === $type)>{{ strtoupper($type) }}</option>@endforeach</select></div>
                        <div class="field"><label>{{ __("Quality") }}</label><select name="quality"><option value="">{{ $item->qualityLabel() }}</option>@foreach(['SD', 'HD', 'FHD', '4K', 'Auto'] as $quality)<option value="{{ $quality }}" @selected($item->pivot->quality === $quality)>{{ $quality }}</option>@endforeach</select></div>
                        <div class="field"><label>{{ __("Language") }}</label><input name="language" maxlength="60" value="{{ $item->pivot->language }}" placeholder="{{ __("Arabic") }}"></div>
                        <div class="field"><label>{{ __("Commentator") }}</label><input name="commentator" maxlength="120" value="{{ $item->pivot->commentator }}" placeholder="{{ $worldCupMatch->commentator }}"></div>
                        <div class="field"><label>{{ __("Health") }}</label><select name="health_status"><option value="">{{ __("Unknown") }}</option>@foreach(['unknown', 'online', 'offline', 'failed'] as $health)<option value="{{ $health }}" @selected($item->pivot->health_status === $health)>{{ str($health)->headline() }}</option>@endforeach</select></div>
                        <div class="field"><label>{{ __("Priority") }}</label><input type="number" name="priority" min="0" max="999" value="{{ $item->pivot->priority }}"></div>
                        <div class="field"><label>{{ __("Starts at") }}</label><input type="datetime-local" name="starts_at" value="{{ $item->pivot->starts_at ? \Illuminate\Support\Carbon::parse($item->pivot->starts_at)->format('Y-m-d\TH:i') : '' }}"></div>
                        <div class="field"><label>{{ __("Expires at") }}</label><input type="datetime-local" name="expires_at" value="{{ $item->pivot->expires_at ? \Illuminate\Support\Carbon::parse($item->pivot->expires_at)->format('Y-m-d\TH:i') : '' }}"></div>
                    </div>
                    <div class="wc-admin-stream-actions">
                        <label class="checkbox-field"><input type="checkbox" name="is_active" value="1" @checked($item->pivot->is_active)><span>{{ __("Active") }}</span></label>
                        <label class="checkbox-field"><input type="checkbox" name="is_recommended" value="1" @checked($item->pivot->is_recommended)><span>{{ __("Recommended") }}</span></label>
                        <a class="button button--ghost" href="{{ route('matches.watch', $worldCupMatch) }}" target="_blank" rel="noopener">{{ __("Test page") }}</a>
                        <button class="button button--primary" type="submit">{{ __("Save link") }}</button>
                    </div>
                </form>
            @endforeach
        </div>
    </section>
@endif
<form method="POST" action="{{ route('admin.world-cup-matches.destroy', $worldCupMatch) }}" class="wc-delete-form" onsubmit="return confirm(@js(__('Delete this match?')));">
    @csrf
    @method('DELETE')
    <button class="button button--danger" type="submit">{{ __("Delete match") }}</button>
</form>
@endsection

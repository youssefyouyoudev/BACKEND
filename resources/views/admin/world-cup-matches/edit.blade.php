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
            <div><p class="surface-card__eyebrow">{{ __("Match watch links") }}</p><h2>{{ __("Assigned IPTV servers") }}</h2></div>
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
                    <label class="checkbox-field"><input type="checkbox" name="is_active" value="1" @checked($item->pivot->is_active)><span>{{ __("Active") }}</span></label>
                    <div class="field"><label>{{ __("Priority") }}</label><input type="number" name="priority" min="0" max="999" value="{{ $item->pivot->priority }}"></div>
                    <div class="field"><label>{{ __("Starts at") }}</label><input type="datetime-local" name="starts_at" value="{{ $item->pivot->starts_at ? \Illuminate\Support\Carbon::parse($item->pivot->starts_at)->format('Y-m-d\TH:i') : '' }}"></div>
                    <div class="field"><label>{{ __("Expires at") }}</label><input type="datetime-local" name="expires_at" value="{{ $item->pivot->expires_at ? \Illuminate\Support\Carbon::parse($item->pivot->expires_at)->format('Y-m-d\TH:i') : '' }}"></div>
                    <button class="button button--primary" type="submit">{{ __("Save") }}</button>
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

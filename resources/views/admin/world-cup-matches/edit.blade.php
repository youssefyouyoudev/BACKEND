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
<form method="POST" action="{{ route('admin.world-cup-matches.destroy', $worldCupMatch) }}" class="wc-delete-form" onsubmit="return confirm(@js(__('Delete this match?')));">
    @csrf
    @method('DELETE')
    <button class="button button--danger" type="submit">{{ __("Delete match") }}</button>
</form>
@endsection

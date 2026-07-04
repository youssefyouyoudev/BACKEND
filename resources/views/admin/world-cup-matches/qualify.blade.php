@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("Knockout qualification") }}</p>
        <h1>{{ __("Match") }} {{ $worldCupMatch->match_number }}: {{ $worldCupMatch->home_display_name }} {{ __("vs") }} {{ $worldCupMatch->away_display_name }}</h1>
        <p class="page-header__copy">{{ __("Choose the team that moved on. The bracket will update automatically.") }}</p>
    </div>
    <div class="page-header__actions">
        <a class="button button--ghost" href="{{ route('admin.world-cup-matches.edit', $worldCupMatch) }}">{{ __("Edit match") }}</a>
        <a class="button button--ghost" href="{{ route('admin.world-cup-matches.index') }}">{{ __("Back to matches") }}</a>
    </div>
</section>

<form method="POST" action="{{ route('admin.world-cup-matches.qualify.update', $worldCupMatch) }}" class="wc-match-form">
    @csrf

    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ $worldCupMatch->public_stage_label }}</p>
                <h2>{{ __("Choose the team that moved on") }}</h2>
            </div>
        </div>

        @if($errors->any())
            <div class="legal-callout">
                <strong>{{ __("Please review the selection") }}</strong>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        @if(session('status'))
            <div class="legal-callout">
                <strong>{{ session('status') }}</strong>
            </div>
        @endif

        <div class="form-grid">
            <div class="field">
                <label>{{ __("Match number") }}</label>
                <input value="{{ $worldCupMatch->match_number }}" disabled>
            </div>
            <div class="field">
                <label>{{ __("Stage") }}</label>
                <input value="{{ $worldCupMatch->public_stage_label }}" disabled>
            </div>
            <div class="field">
                <label>{{ __("Date/time in Morocco") }}</label>
                <input value="{{ $worldCupMatch->kickoff_at_morocco?->format('M d, Y H:i') ?: __('To be confirmed') }}" disabled>
            </div>
            <div class="field">
                <label>{{ __("Venue") }}</label>
                <input value="{{ collect([$worldCupMatch->venue, $worldCupMatch->city])->filter()->implode(', ') ?: __('To be confirmed') }}" disabled>
            </div>
        </div>

        @if($worldCupMatch->hasQualifiedTeam())
            <div class="legal-callout">
                <strong>{{ __("Current qualified team") }}</strong>
                <p>{{ __("Qualified: :team", ['team' => $worldCupMatch->qualified_team]) }}</p>
            </div>
        @endif

        <div class="form-grid">
            <label class="checkbox-field">
                <input type="radio" name="side" value="home" @checked(old('side', $worldCupMatch->qualified_side) === 'home')>
                <span>{{ __("Home team qualified") }} - {{ $worldCupMatch->home_display_name }}</span>
            </label>
            <label class="checkbox-field">
                <input type="radio" name="side" value="away" @checked(old('side', $worldCupMatch->qualified_side) === 'away')>
                <span>{{ __("Away team qualified") }} - {{ $worldCupMatch->away_display_name }}</span>
            </label>
        </div>
    </section>

    <div class="wc-sticky-save">
        <button class="button button--primary" type="submit">{{ __("Save qualified team") }}</button>
        <a class="button button--ghost" href="{{ route('matches.watch', $worldCupMatch) }}" target="_blank" rel="noopener">{{ __("Preview Watch Page") }}</a>
    </div>
</form>
@endsection

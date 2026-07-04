@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("Knockout result") }}</p>
        <h1>{{ __("Match") }} {{ $worldCupMatch->match_number }}: {{ $worldCupMatch->home_display_name }} {{ __("vs") }} {{ $worldCupMatch->away_display_name }}</h1>
        <p class="page-header__copy">{{ __("Save the score, choose the winner, and advance the qualified team through the World Cup bracket.") }}</p>
    </div>
    <div class="page-header__actions">
        <a class="button button--ghost" href="{{ route('admin.world-cup-matches.edit', $worldCupMatch) }}">{{ __("Edit match") }}</a>
        <a class="button button--ghost" href="{{ route('admin.world-cup-matches.index') }}">{{ __("Back to matches") }}</a>
    </div>
</section>

<form method="POST" action="{{ route('admin.world-cup-matches.result.update', $worldCupMatch) }}" class="wc-match-form">
    @csrf

    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ $worldCupMatch->public_stage_label }}</p>
                <h2>{{ __("Winner management") }}</h2>
            </div>
        </div>

        @if($errors->any())
            <div class="legal-callout">
                <strong>{{ __("Please review the result") }}</strong>
                <p>{{ $errors->first() }}</p>
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
                <label>{{ __("Home team") }}</label>
                <input value="{{ $worldCupMatch->home_display_name }}" disabled>
            </div>
            <div class="field">
                <label>{{ __("Away team") }}</label>
                <input value="{{ $worldCupMatch->away_display_name }}" disabled>
            </div>
            <div class="field">
                <label for="home_score">{{ __("Home score") }}</label>
                <input id="home_score" type="number" min="0" name="home_score" value="{{ old('home_score', $worldCupMatch->home_score) }}">
            </div>
            <div class="field">
                <label for="away_score">{{ __("Away score") }}</label>
                <input id="away_score" type="number" min="0" name="away_score" value="{{ old('away_score', $worldCupMatch->away_score) }}">
            </div>
            <div class="field">
                <label for="home_penalties">{{ __("Home penalties") }}</label>
                <input id="home_penalties" type="number" min="0" name="home_penalties" value="{{ old('home_penalties', $worldCupMatch->home_penalties) }}">
            </div>
            <div class="field">
                <label for="away_penalties">{{ __("Away penalties") }}</label>
                <input id="away_penalties" type="number" min="0" name="away_penalties" value="{{ old('away_penalties', $worldCupMatch->away_penalties) }}">
            </div>
            <div class="field">
                <label for="winner_side">{{ __("Winner") }}</label>
                <select id="winner_side" name="winner_side" required>
                    <option value="home" @selected(old('winner_side', $worldCupMatch->winner_team === $worldCupMatch->home_team ? 'home' : null) === 'home')>{{ __("Home team") }} - {{ $worldCupMatch->home_display_name }}</option>
                    <option value="away" @selected(old('winner_side', $worldCupMatch->winner_team === $worldCupMatch->away_team ? 'away' : null) === 'away')>{{ __("Away team") }} - {{ $worldCupMatch->away_display_name }}</option>
                </select>
            </div>
            <div class="field">
                <label for="status">{{ __("Status") }}</label>
                <select id="status" name="status" required>
                    @foreach($resultStatuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $worldCupMatch->status ?: 'scheduled') === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ __("Preview") }}</p>
                <h2>{{ __("Bracket advancement") }}</h2>
            </div>
        </div>
        <div class="legal-callout">
            <strong>
                {{ $nextWinnerMatch
                    ? __("Winner will advance to: Match :number", ['number' => $nextWinnerMatch->match_number])
                    : __("Winner path ends here") }}
            </strong>
            <p>
                @if(in_array((int) $worldCupMatch->match_number, [101, 102], true))
                    {{ __("Loser will advance to third-place match.") }}
                @else
                    {{ __("Future slots are updated only while they still contain TBD, W/L placeholders, or the matching placeholder text.") }}
                @endif
            </p>
        </div>
    </section>

    <div class="wc-sticky-save">
        <button class="button button--primary" type="submit">{{ __("Save result") }}</button>
        <a class="button button--ghost" href="{{ route('matches.watch', $worldCupMatch) }}" target="_blank" rel="noopener">{{ __("Preview Watch Page") }}</a>
    </div>
</form>
@endsection

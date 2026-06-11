@extends('layouts.app')

@section('title', __("Football Standings & League Tables | RifiMedia"))
@section('description', __("Follow football standings, league tables, points, form, and league movement on RifiMedia."))

@php
    $standings = collect($standings ?? []);
@endphp

@section('content')
<div class="rm-page rm-page--standings">
    <section class="rm-page-hero rm-standings-hero" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <span class="rm-kicker"><x-icon name="trophy" /> {{ __("Standings") }}</span>
        <h1>{{ __("Football standings and league tables") }}</h1>
        <p>{{ __("Track league positions, points, form, and match-day movement in a clean RifiMedia table view.") }}</p>
    </section>

    <x-ad-slot name="standings_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div class="rm-standings-surface">
            @if($standings->isNotEmpty())
                <div class="rm-section-header">
                    <div>
                        <p class="rm-eyebrow">{{ __("League table") }}</p>
                        <h2>{{ __("Current standings") }}</h2>
                    </div>
                </div>

                <div class="rm-standings-table-wrap" role="region" aria-label="{{ __("Football standings table") }}" tabindex="0">
                    <table class="rm-standings-table">
                        <caption class="sr-only">{{ __("Football league standings") }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __("Position") }}</th>
                                <th scope="col">{{ __("Team") }}</th>
                                <th scope="col">{{ __("Played") }}</th>
                                <th scope="col">{{ __("Won") }}</th>
                                <th scope="col">{{ __("Drawn") }}</th>
                                <th scope="col">{{ __("Lost") }}</th>
                                <th scope="col">{{ __("Goals") }}</th>
                                <th scope="col">{{ __("Points") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standings as $row)
                                @php
                                    $teamName = data_get($row, 'team.name') ?: data_get($row, 'team') ?: data_get($row, 'name') ?: 'Team';
                                    $logo = data_get($row, 'team.logo') ?: data_get($row, 'logo') ?: asset('brand/rifi-logo.png');
                                    $goals = data_get($row, 'goals') ?? trim((string) data_get($row, 'goals_for', '-').' - '.(string) data_get($row, 'goals_against', '-'));
                                @endphp
                                <tr>
                                    <td data-label="{{ __("Position") }}"><strong>{{ data_get($row, 'position', $loop->iteration) }}</strong></td>
                                    <td data-label="{{ __("Team") }}">
                                        <span class="rm-standings-team">
                                            <img src="{{ $logo }}" alt="" loading="lazy" data-fallback-src="{{ asset('brand/rifi-logo.png') }}">
                                            <strong>{{ $teamName }}</strong>
                                        </span>
                                    </td>
                                    <td data-label="{{ __("Played") }}">{{ data_get($row, 'played', '-') }}</td>
                                    <td data-label="{{ __("Won") }}">{{ data_get($row, 'won', '-') }}</td>
                                    <td data-label="{{ __("Drawn") }}">{{ data_get($row, 'drawn', '-') }}</td>
                                    <td data-label="{{ __("Lost") }}">{{ data_get($row, 'lost', '-') }}</td>
                                    <td data-label="{{ __("Goals") }}">{{ $goals ?: '-' }}</td>
                                    <td data-label="{{ __("Points") }}"><strong>{{ data_get($row, 'points', '-') }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <section class="rm-standings-empty" aria-labelledby="rm-standings-empty-title">
                    <span class="rm-standings-empty__icon"><x-icon name="trophy" /></span>
                    <p class="rm-eyebrow">{{ __("League tables") }}</p>
                    <h2 id="rm-standings-empty-title">{{ __("Standings are not available right now") }}</h2>
                    <p>{{ __("Check football scores and fixtures while league tables are updated.") }}</p>
                    <div class="rm-hero-actions">
                        <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-primary"><x-icon name="scores" />{{ __("View Scores") }}</a>
                        <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-secondary"><x-icon name="play" />{{ __("Explore Live TV") }}</a>
                    </div>
                </section>
            @endif
        </div>

        <aside class="rm-side-rail">
            <x-ad-slot name="standings_sidebar_rectangle" size="rectangle" />
            @if($leagues->isNotEmpty())
                <div class="rm-topic-card">
                    <h2>{{ __("League pages") }}</h2>
                    @foreach($leagues as $league)
                        <a href="{{ route('leagues.show', $league['slug']) }}">{{ $league['name'] }}</a>
                    @endforeach
                </div>
            @endif
        </aside>
    </section>
</div>
@endsection

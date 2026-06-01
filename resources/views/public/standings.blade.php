@extends('layouts.app')

@section('title', 'Football Standings & League Tables | RifiMedia')
@section('description', 'Follow football standings, league tables, points, form, and league movement on RifiMedia.')

@php
    $standings = collect($standings ?? []);
@endphp

@section('content')
<div class="rm-page rm-page--standings">
    <section class="rm-page-hero rm-standings-hero" style="--rm-hero-photo: url('{{ config('rifimedia_visuals.images.stadium_night') }}')">
        <span class="rm-kicker"><x-icon name="trophy" /> Standings</span>
        <h1>Football standings and league tables</h1>
        <p>Track league positions, points, form, and match-day movement in a clean RifiMedia table view.</p>
    </section>

    <x-ad-slot name="standings_leaderboard" size="leaderboard" />

    <section class="rm-section rm-layout-with-rail">
        <div class="rm-standings-surface">
            @if($standings->isNotEmpty())
                <div class="rm-section-header">
                    <div>
                        <p class="rm-eyebrow">League table</p>
                        <h2>Current standings</h2>
                    </div>
                </div>

                <div class="rm-standings-table-wrap" role="region" aria-label="Football standings table" tabindex="0">
                    <table class="rm-standings-table">
                        <caption class="sr-only">Football league standings</caption>
                        <thead>
                            <tr>
                                <th scope="col">Position</th>
                                <th scope="col">Team</th>
                                <th scope="col">Played</th>
                                <th scope="col">Won</th>
                                <th scope="col">Drawn</th>
                                <th scope="col">Lost</th>
                                <th scope="col">Goals</th>
                                <th scope="col">Points</th>
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
                                    <td data-label="Position"><strong>{{ data_get($row, 'position', $loop->iteration) }}</strong></td>
                                    <td data-label="Team">
                                        <span class="rm-standings-team">
                                            <img src="{{ $logo }}" alt="" loading="lazy" data-fallback-src="{{ asset('brand/rifi-logo.png') }}">
                                            <strong>{{ $teamName }}</strong>
                                        </span>
                                    </td>
                                    <td data-label="Played">{{ data_get($row, 'played', '-') }}</td>
                                    <td data-label="Won">{{ data_get($row, 'won', '-') }}</td>
                                    <td data-label="Drawn">{{ data_get($row, 'drawn', '-') }}</td>
                                    <td data-label="Lost">{{ data_get($row, 'lost', '-') }}</td>
                                    <td data-label="Goals">{{ $goals ?: '-' }}</td>
                                    <td data-label="Points"><strong>{{ data_get($row, 'points', '-') }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <section class="rm-standings-empty" aria-labelledby="rm-standings-empty-title">
                    <span class="rm-standings-empty__icon"><x-icon name="trophy" /></span>
                    <p class="rm-eyebrow">League tables</p>
                    <h2 id="rm-standings-empty-title">Standings are not available right now</h2>
                    <p>Check football scores and fixtures while league tables are updated.</p>
                    <div class="rm-hero-actions">
                        <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-primary"><x-icon name="scores" />View Scores</a>
                        <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-secondary"><x-icon name="play" />Explore Live TV</a>
                    </div>
                </section>
            @endif
        </div>

        <aside class="rm-side-rail">
            <x-ad-slot name="standings_sidebar_rectangle" size="rectangle" />
            @if($leagues->isNotEmpty())
                <div class="rm-topic-card">
                    <h2>League pages</h2>
                    @foreach($leagues as $league)
                        <a href="{{ route('leagues.show', $league['slug']) }}">{{ $league['name'] }}</a>
                    @endforeach
                </div>
            @endif
        </aside>
    </section>
</div>
@endsection

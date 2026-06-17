<?php

use App\Models\WorldCupMatch;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('marks matches older than three hours as ended', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-17 18:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $match = createAutoEndMatch([
        'kickoff_at' => CarbonImmutable::parse('2026-06-17 14:30:00', WorldCupMatch::MOROCCO_TIMEZONE)->utc(),
        'broadcast_status' => 'live',
        'is_live_link_enabled' => true,
    ]);

    $this->artisan('matches:auto-end-old')
        ->expectsOutput('Auto-ended 1 old matches.')
        ->assertSuccessful();

    expect($match->fresh())
        ->broadcast_status->toBe(WorldCupMatch::STATUS_ENDED)
        ->ended_at->not->toBeNull()
        ->status_updated_by->toBe('automatic')
        ->is_live_link_enabled->toBeFalse();
});

it('does not change ended cancelled or postponed matches', function (string $status) {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-17 18:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $match = createAutoEndMatch([
        'kickoff_at' => CarbonImmutable::parse('2026-06-17 12:00:00', WorldCupMatch::MOROCCO_TIMEZONE)->utc(),
        'broadcast_status' => $status,
        'status_updated_by' => 'admin',
    ]);

    $this->artisan('matches:auto-end-old')->assertSuccessful();

    expect($match->fresh())
        ->broadcast_status->toBe($status)
        ->status_updated_by->toBe('admin');
})->with(['ended', 'cancelled', 'postponed']);

it('keeps upcoming matches under three hours old unchanged', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-17 18:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $match = createAutoEndMatch([
        'kickoff_at' => CarbonImmutable::parse('2026-06-17 15:30:00', WorldCupMatch::MOROCCO_TIMEZONE)->utc(),
        'broadcast_status' => 'scheduled',
    ]);

    $this->artisan('matches:auto-end-old')->assertSuccessful();

    expect($match->fresh())
        ->broadcast_status->toBe('scheduled')
        ->ended_at->toBeNull();
});

it('shows a virtual finished status before the scheduler runs', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-06-17 18:00:00', WorldCupMatch::MOROCCO_TIMEZONE));

    $match = createAutoEndMatch([
        'kickoff_at' => CarbonImmutable::parse('2026-06-17 14:30:00', WorldCupMatch::MOROCCO_TIMEZONE)->utc(),
        'morocco_kickoff_at' => '2026-06-17 14:30:00',
        'broadcast_status' => 'scheduled',
    ]);

    expect($match->fresh())
        ->public_status->toBe('finished')
        ->public_status_label->toBe('Finished');
});

function createAutoEndMatch(array $overrides = []): WorldCupMatch
{
    return WorldCupMatch::query()->create(array_merge([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'kickoff_at' => now()->addDay(),
        'morocco_kickoff_at' => now()->addDay(),
        'broadcast_status' => 'scheduled',
    ], $overrides));
}

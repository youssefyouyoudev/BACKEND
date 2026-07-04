<?php

namespace App\Services;

use App\Models\WorldCupMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorldCupKnockoutService
{
    private ?string $previousQualifiedTeam = null;

    private ?string $previousEliminatedTeam = null;

    public function qualifyTeam(WorldCupMatch $match, string $side): WorldCupMatch
    {
        return DB::transaction(function () use ($match, $side): WorldCupMatch {
            $match = $match->newQuery()->lockForUpdate()->findOrFail($match->getKey());
            $this->ensureKnockoutMatch($match);
            $this->previousQualifiedTeam = $match->qualified_team;
            $this->previousEliminatedTeam = $match->eliminated_team;

            $qualifiedTeam = $this->getTeamNameFromSide($match, $side);
            $eliminatedSide = $side === 'home' ? 'away' : 'home';
            $eliminatedTeam = $this->getTeamNameFromSide($match, $eliminatedSide);

            $match->fill([
                'qualified_team' => $qualifiedTeam,
                'eliminated_team' => $eliminatedTeam,
                'qualified_side' => $side,
                'status' => 'completed',
                'broadcast_status' => WorldCupMatch::STATUS_ENDED,
                'advanced_at' => now('UTC'),
            ])->save();

            $this->advanceWinnerToNextRound($match);
            $this->advanceLoserToThirdPlaceIfSemiFinal($match);

            return $match->fresh();
        });
    }

    public function getTeamNameFromSide(WorldCupMatch $match, string $side): string
    {
        if (! in_array($side, ['home', 'away'], true)) {
            throw ValidationException::withMessages([
                'side' => __('Choose the home team or away team.'),
            ]);
        }

        $teamName = trim((string) $match->getAttribute($side.'_team'));
        $placeholder = trim((string) $match->getAttribute($side.'_placeholder'));

        if ($teamName === '' || $this->isPlaceholderTeam($teamName, $placeholder)) {
            throw ValidationException::withMessages([
                'side' => __('Both teams must be known before choosing who qualified.'),
            ]);
        }

        return $teamName;
    }

    public function advanceWinnerToNextRound(WorldCupMatch $match): void
    {
        if (! $match->qualified_team || ! $match->match_number) {
            return;
        }

        $this->updateFutureSlot('W'.$match->match_number, $match->qualified_team);
    }

    public function advanceLoserToThirdPlaceIfSemiFinal(WorldCupMatch $match): void
    {
        if (! $match->eliminated_team || ! in_array((int) $match->match_number, [101, 102], true)) {
            return;
        }

        $this->updateFutureSlot('L'.$match->match_number, $match->eliminated_team);
    }

    public function updateFutureSlot(string $sourceCode, string $teamName): void
    {
        WorldCupMatch::query()
            ->where(function ($query) use ($sourceCode): void {
                $query->where('home_placeholder', $sourceCode)
                    ->orWhere('away_placeholder', $sourceCode);
            })
            ->orderBy('match_number')
            ->lockForUpdate()
            ->get()
            ->each(function (WorldCupMatch $futureMatch) use ($sourceCode, $teamName): void {
                $updates = [];

                if ($futureMatch->home_placeholder === $sourceCode && $this->canOverwriteFutureSlot($futureMatch->home_team, $sourceCode)) {
                    $updates['home_team'] = $teamName;
                }

                if ($futureMatch->away_placeholder === $sourceCode && $this->canOverwriteFutureSlot($futureMatch->away_team, $sourceCode)) {
                    $updates['away_team'] = $teamName;
                }

                if ($updates !== []) {
                    $futureMatch->fill($updates)->save();
                }
            });
    }

    private function ensureKnockoutMatch(WorldCupMatch $match): void
    {
        if (! $match->isKnockout()) {
            throw ValidationException::withMessages([
                'match' => __('Qualification can only be managed for World Cup knockout matches.'),
            ]);
        }
    }

    private function canOverwriteFutureSlot(?string $teamName, string $sourceCode): bool
    {
        $teamName = trim((string) $teamName);

        if ($teamName === '') {
            return true;
        }

        if (in_array(Str::lower($teamName), ['tbd', 'to be confirmed', 'team to be confirmed'], true)) {
            return true;
        }

        if ($this->previousQualifiedTeam && $teamName === $this->previousQualifiedTeam) {
            return true;
        }

        if ($this->previousEliminatedTeam && $teamName === $this->previousEliminatedTeam) {
            return true;
        }

        return $this->isPlaceholderTeam($teamName, $sourceCode);
    }

    private function isPlaceholderTeam(string $teamName, ?string $placeholder = null): bool
    {
        $normalized = Str::lower(trim($teamName));
        $placeholder = trim((string) $placeholder);

        if ($placeholder !== '' && $normalized === Str::lower($placeholder)) {
            return true;
        }

        return preg_match('/^(winner|loser)\s+match\s+\d+$/i', $teamName) === 1
            || preg_match('/^[WL]\d+$/i', $teamName) === 1
            || in_array($normalized, ['tbd', 'to be confirmed', 'home team to be confirmed', 'away team to be confirmed'], true);
    }
}

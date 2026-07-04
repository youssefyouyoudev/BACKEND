<?php

namespace App\Services;

use App\Models\WorldCupMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorldCupKnockoutService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveResult(WorldCupMatch $match, array $data): WorldCupMatch
    {
        return DB::transaction(function () use ($match, $data): WorldCupMatch {
            $match = $match->newQuery()->lockForUpdate()->findOrFail($match->getKey());
            $this->ensureKnockoutMatch($match);

            $status = (string) ($data['status'] ?? 'scheduled');
            $resultData = [
                'home_score' => $data['home_score'] ?? null,
                'away_score' => $data['away_score'] ?? null,
                'home_penalties' => $data['home_penalties'] ?? null,
                'away_penalties' => $data['away_penalties'] ?? null,
                'status' => $status,
                'broadcast_status' => $this->broadcastStatusFor($status),
            ];

            if ($status !== 'completed') {
                $match->fill($resultData)->save();

                return $match->fresh();
            }

            $winnerSide = (string) ($data['winner_side'] ?? '');
            $resolved = $this->resolveWinner($match->fill($resultData), $winnerSide);

            $match->fill([
                ...$resultData,
                'winner_team' => $resolved['winner_team'],
                'loser_team' => $resolved['loser_team'],
                'winner_source' => 'W'.$match->match_number,
                'loser_source' => 'L'.$match->match_number,
                'winner_match_number' => $match->match_number,
                'loser_match_number' => $match->match_number,
                'played_at' => $data['played_at'] ?? now('UTC'),
            ])->save();

            $this->advanceWinner($match);
            $this->advanceLoserForThirdPlace($match);

            return $match->fresh();
        });
    }

    /**
     * @return array{winner_team: string, loser_team: string, winner_side: string, loser_side: string}
     */
    public function resolveWinner(WorldCupMatch $match, string $winnerSide): array
    {
        if (! in_array($winnerSide, ['home', 'away'], true)) {
            throw ValidationException::withMessages([
                'winner_side' => __('Choose the home team or away team as winner.'),
            ]);
        }

        $homeTeam = $this->confirmedTeamName($match, 'home');
        $awayTeam = $this->confirmedTeamName($match, 'away');
        $this->ensureCompletedScoresAreValid($match, $winnerSide);

        return [
            'winner_team' => $winnerSide === 'home' ? $homeTeam : $awayTeam,
            'loser_team' => $winnerSide === 'home' ? $awayTeam : $homeTeam,
            'winner_side' => $winnerSide,
            'loser_side' => $winnerSide === 'home' ? 'away' : 'home',
        ];
    }

    public function advanceWinner(WorldCupMatch $match): void
    {
        if (! $match->winner_team || ! $match->match_number) {
            return;
        }

        $this->updateFutureMatchSlot((int) $match->match_number, 'W'.$match->match_number, $match->winner_team);
    }

    public function advanceLoserForThirdPlace(WorldCupMatch $match): void
    {
        if (! $match->loser_team || ! in_array((int) $match->match_number, [101, 102], true)) {
            return;
        }

        $this->updateFutureMatchSlot((int) $match->match_number, 'L'.$match->match_number, $match->loser_team);
    }

    public function updateFutureMatchSlot(int $sourceMatchNumber, string $sourceCode, string $teamName): void
    {
        WorldCupMatch::query()
            ->where('match_number', '>', $sourceMatchNumber)
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

    public function nextWinnerMatch(WorldCupMatch $match): ?WorldCupMatch
    {
        if (! $match->match_number) {
            return null;
        }

        $sourceCode = 'W'.$match->match_number;

        return WorldCupMatch::query()
            ->where('match_number', '>', $match->match_number)
            ->where(fn ($query) => $query
                ->where('home_placeholder', $sourceCode)
                ->orWhere('away_placeholder', $sourceCode))
            ->orderBy('match_number')
            ->first();
    }

    private function ensureKnockoutMatch(WorldCupMatch $match): void
    {
        if (! $match->is_knockout && ! ((int) $match->match_number >= 73 && (int) $match->match_number <= 104)) {
            throw ValidationException::withMessages([
                'match' => __('Results can only be managed here for World Cup knockout matches.'),
            ]);
        }
    }

    private function confirmedTeamName(WorldCupMatch $match, string $side): string
    {
        $team = trim((string) $match->getAttribute($side.'_team'));
        $placeholder = trim((string) $match->getAttribute($side.'_placeholder'));

        if ($team === '' || $this->isPlaceholderTeam($team, $placeholder)) {
            throw ValidationException::withMessages([
                $side.'_team' => __('Resolve both teams before choosing a knockout winner.'),
            ]);
        }

        return $team;
    }

    private function ensureCompletedScoresAreValid(WorldCupMatch $match, string $winnerSide): void
    {
        if ($match->home_score === null || $match->away_score === null) {
            throw ValidationException::withMessages([
                'home_score' => __('Enter both scores before completing a knockout result.'),
            ]);
        }

        if ($match->home_score === $match->away_score) {
            if ($match->home_penalties === null || $match->away_penalties === null) {
                throw ValidationException::withMessages([
                    'home_penalties' => __('Enter penalties when the knockout score is tied.'),
                ]);
            }

            if ($match->home_penalties === $match->away_penalties) {
                throw ValidationException::withMessages([
                    'home_penalties' => __('Penalty scores cannot be tied when choosing a winner.'),
                ]);
            }

            $penaltyWinner = $match->home_penalties > $match->away_penalties ? 'home' : 'away';

            if ($winnerSide !== $penaltyWinner) {
                throw ValidationException::withMessages([
                    'winner_side' => __('The selected winner must match the penalty score.'),
                ]);
            }

            return;
        }

        $scoreWinner = $match->home_score > $match->away_score ? 'home' : 'away';

        if ($winnerSide !== $scoreWinner) {
            throw ValidationException::withMessages([
                'winner_side' => __('The selected winner must match the match score.'),
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

    private function broadcastStatusFor(string $status): string
    {
        return match ($status) {
            'completed' => WorldCupMatch::STATUS_ENDED,
            'live' => 'live',
            'cancelled' => 'cancelled',
            default => 'scheduled',
        };
    }
}

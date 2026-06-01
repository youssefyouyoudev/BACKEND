<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class TheSportsDbService
{
    public function __construct(private readonly ChannelMatcherService $channelMatcher)
    {
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.the_sports_db.base_url', 'https://www.thesportsdb.com/api/v1/json'), '/');
    }

    public function apiKey(): string
    {
        return trim((string) config('services.the_sports_db.key', '123')) ?: '123';
    }

    /**
     * @return array<string, mixed>
     */
    public function request(string $endpoint, array $query = []): array
    {
        $url = $this->endpoint($endpoint);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->retry(1, 200)
                ->acceptJson()
                ->get($url, array_filter($query, fn ($value): bool => $value !== null && $value !== ''));

            if ($response->status() === 429) {
                return ['ok' => false, 'status' => 429, 'message' => 'TheSportsDB rate limit reached. Please retry shortly.', 'data' => []];
            }

            if (! $response->successful()) {
                return ['ok' => false, 'status' => $response->status(), 'message' => 'TheSportsDB request failed.', 'data' => []];
            }

            return ['ok' => true, 'status' => $response->status(), 'message' => null, 'data' => $this->json($response)];
        } catch (Throwable) {
            return ['ok' => false, 'status' => 0, 'message' => 'TheSportsDB request timed out or could not be reached.', 'data' => []];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEventsByDay(string $date, ?int $leagueId = null): array
    {
        $date = $this->safeDate($date);
        $cacheKey = 'thesportsdb:events-day:'.$date.':'.($leagueId ?: 'all');
        $ttl = $date === now()->toDateString() ? now()->addMinutes(5) : now()->addMinutes(30);

        return Cache::remember($cacheKey, $ttl, function () use ($date, $leagueId): array {
            $payload = $this->request('eventsday.php', [
                'd' => $date,
                'l' => $leagueId,
                's' => 'Soccer',
            ]);

            return $this->normalizeEvents($payload['data']['events'] ?? []);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNextLeagueEvents(int $leagueId): array
    {
        if ($leagueId <= 0) {
            return [];
        }

        return Cache::remember("thesportsdb:next-league:{$leagueId}", now()->addMinutes(30), function () use ($leagueId): array {
            $payload = $this->request('eventsnextleague.php', ['id' => $leagueId]);

            return $this->normalizeEvents($payload['data']['events'] ?? []);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPastLeagueEvents(int $leagueId): array
    {
        if ($leagueId <= 0) {
            return [];
        }

        return Cache::remember("thesportsdb:past-league:{$leagueId}", now()->addMinutes(30), function () use ($leagueId): array {
            $payload = $this->request('eventspastleague.php', ['id' => $leagueId]);

            return $this->normalizeEvents($payload['data']['events'] ?? []);
        });
    }

    public function getEventDetails(string|int $eventId): ?array
    {
        $eventId = $this->sanitizeEventId($eventId);

        if ($eventId === null) {
            return null;
        }

        return Cache::remember("thesportsdb:event:{$eventId}", now()->addMinutes(30), function () use ($eventId): ?array {
            $payload = $this->request('lookupevent.php', ['id' => $eventId]);
            $event = collect($payload['data']['events'] ?? [])->first();

            return is_array($event) ? $this->normalizeEvent($event) : null;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEventTvChannels(string|int $eventId): array
    {
        $eventId = $this->sanitizeEventId($eventId);

        if ($eventId === null) {
            return [];
        }

        $channels = Cache::remember("thesportsdb:event-tv:{$eventId}", now()->addHours(6), function () use ($eventId): array {
            $payload = $this->request('lookuptv.php', ['id' => $eventId]);

            return $this->normalizeTvChannels($payload['data'] ?? []);
        });

        return $this->channelMatcher->enrichTvChannelsWithPlaylistLinks($channels);
    }

    /**
     * Backwards-compatible alias used by existing code.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tvChannelsForEvent(string|int $eventId): array
    {
        return $this->getEventTvChannels($eventId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopLeagueMatchesByDate(string $date): array
    {
        $date = $this->safeDate($date);
        $cacheKey = 'thesportsdb:top-leagues-day:'.$date;
        $ttl = $date === now()->toDateString() ? now()->addMinutes(5) : now()->addMinutes(30);

        return Cache::remember($cacheKey, $ttl, function () use ($date): array {
            return $this->topLeagues()
                ->filter(fn (array $league): bool => filled($league['id'] ?? null))
                ->flatMap(fn (array $league) => $this->getEventsByDay($date, (int) $league['id']))
                ->unique('id')
                ->sortBy([['date', 'asc'], ['time', 'asc']])
                ->values()
                ->all();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingTopLeagueMatches(): array
    {
        return Cache::remember('thesportsdb:top-leagues-upcoming', now()->addMinutes(30), function (): array {
            return $this->topLeagues()
                ->filter(fn (array $league): bool => filled($league['id'] ?? null))
                ->flatMap(fn (array $league) => $this->getNextLeagueEvents((int) $league['id']))
                ->unique('id')
                ->sortBy([['date', 'asc'], ['time', 'asc']])
                ->values()
                ->all();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentTopLeagueResults(): array
    {
        return Cache::remember('thesportsdb:top-leagues-results', now()->addMinutes(30), function (): array {
            return $this->topLeagues()
                ->filter(fn (array $league): bool => filled($league['id'] ?? null))
                ->flatMap(fn (array $league) => $this->getPastLeagueEvents((int) $league['id']))
                ->unique('id')
                ->sortByDesc(fn (array $event): string => ($event['date'] ?? '').' '.($event['time'] ?? ''))
                ->values()
                ->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeEvent(array $event): array
    {
        $status = $this->detectMatchStatus($event);
        $eventId = (string) ($event['idEvent'] ?? '');

        return [
            'id' => $eventId,
            'league' => [
                'id' => (string) ($event['idLeague'] ?? ''),
                'name' => $this->clean($event['strLeague'] ?? null) ?: 'Football',
                'slug' => Str::slug((string) ($event['strLeague'] ?? 'football')),
            ],
            'home_team' => [
                'id' => (string) ($event['idHomeTeam'] ?? ''),
                'name' => $this->clean($event['strHomeTeam'] ?? null) ?: 'Home',
                'badge' => $this->image($event['strHomeTeamBadge'] ?? $event['strHomeTeamLogo'] ?? null),
            ],
            'away_team' => [
                'id' => (string) ($event['idAwayTeam'] ?? ''),
                'name' => $this->clean($event['strAwayTeam'] ?? null) ?: 'Away',
                'badge' => $this->image($event['strAwayTeamBadge'] ?? $event['strAwayTeamLogo'] ?? null),
            ],
            'score' => [
                'home' => $this->score($event['intHomeScore'] ?? null),
                'away' => $this->score($event['intAwayScore'] ?? null),
            ],
            'date' => $this->clean($event['dateEvent'] ?? null),
            'time' => $this->formatTime($event['strTime'] ?? null),
            'status' => $status['status'],
            'status_type' => $status['status_type'],
            'venue' => $this->clean($event['strVenue'] ?? null),
            'round' => $this->clean($event['intRound'] ?? null) ? 'Round '.$this->clean($event['intRound'] ?? null) : null,
            'poster' => $this->image($event['strPoster'] ?? $event['strThumb'] ?? $event['strFanart'] ?? null),
            'tv_channels' => [],
            'event_url' => $eventId !== '' ? route('sports.football.event', $eventId) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $tvData
     * @return array<int, array{channel: string, country: ?string}>
     */
    public function normalizeTvChannels(array $tvData): array
    {
        $rows = $tvData['tvevent'] ?? $tvData['tv'] ?? $tvData['events'] ?? $tvData['broadcasts'] ?? [];

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $channel = $this->clean(Arr::first([
                    $row['strChannel'] ?? null,
                    $row['strStation'] ?? null,
                    $row['strNetwork'] ?? null,
                    $row['strChannelName'] ?? null,
                    $row['channel'] ?? null,
                    $row['name'] ?? null,
                ], fn ($value): bool => filled($value)));

                if (! $channel) {
                    return null;
                }

                return [
                    'channel' => $channel,
                    'country' => $this->clean($row['strCountry'] ?? $row['strLocale'] ?? $row['country'] ?? null),
                ];
            })
            ->filter()
            ->unique(fn (array $row): string => mb_strtolower($row['channel'].'|'.($row['country'] ?? '')))
            ->values()
            ->all();
    }

    /**
     * @return array{status: string, status_type: string}
     */
    public function detectMatchStatus(array $event): array
    {
        $status = mb_strtolower((string) ($event['strStatus'] ?? $event['strProgress'] ?? ''));
        $progress = mb_strtolower((string) ($event['strProgress'] ?? ''));
        $date = $this->clean($event['dateEvent'] ?? null);
        $homeScore = $event['intHomeScore'] ?? null;
        $awayScore = $event['intAwayScore'] ?? null;

        if (str_contains($status, 'postpon')) {
            return ['status' => 'Postponed', 'status_type' => 'postponed'];
        }

        if (str_contains($status, 'cancel')) {
            return ['status' => 'Cancelled', 'status_type' => 'cancelled'];
        }

        if (str_contains($status, 'half')) {
            return ['status' => 'Halftime', 'status_type' => 'halftime'];
        }

        if (str_contains($status, 'match finished') || str_contains($status, 'finished') || str_contains($status, 'ft')) {
            return ['status' => 'Finished', 'status_type' => 'finished'];
        }

        if (preg_match('/\b(?:live|1h|2h|et|pen|\d{1,3}\')\b/u', $status.' '.$progress) === 1) {
            return ['status' => 'Live', 'status_type' => 'live'];
        }

        if ($date && $date < now()->toDateString() && ($homeScore !== null || $awayScore !== null)) {
            return ['status' => 'Finished', 'status_type' => 'finished'];
        }

        if ($date && $date >= now()->toDateString()) {
            return ['status' => 'Not Started', 'status_type' => 'not_started'];
        }

        return ['status' => 'Unknown', 'status_type' => 'unknown'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeEvents(mixed $events): array
    {
        if (! is_array($events)) {
            return [];
        }

        return collect($events)
            ->filter(fn ($event): bool => is_array($event))
            ->map(fn (array $event): array => $this->normalizeEvent($event))
            ->values()
            ->all();
    }

    private function endpoint(string $path): string
    {
        return $this->baseUrl().'/'.$this->apiKey().'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function safeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            return now()->toDateString();
        }
    }

    private function sanitizeEventId(string|int $eventId): ?string
    {
        $eventId = trim((string) $eventId);

        return preg_match('/^\d+$/', $eventId) === 1 ? $eventId : null;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = Str::of((string) $value)->squish()->limit(180, '')->toString();

        return $value !== '' ? $value : null;
    }

    private function image(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    private function score(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function formatTime(mixed $value): ?string
    {
        $value = $this->clean($value);

        if (! $value) {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}/', $value, $matches) === 1 ? $matches[0] : $value;
    }

    private function topLeagues(): \Illuminate\Support\Collection
    {
        return collect(config('football_leagues.top_leagues', []));
    }
}

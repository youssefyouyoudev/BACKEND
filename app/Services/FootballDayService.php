<?php

namespace App\Services;

use App\Models\WorldCupMatch;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class FootballDayService
{
    public const START_HOUR = 6;

    public const END_TIME = '05:59:59';

    public const DEFAULT_TIMEZONE = WorldCupMatch::MOROCCO_TIMEZONE;

    /**
     * @return array{football_date: string, start: CarbonImmutable, end: CarbonImmutable, start_utc: CarbonImmutable, end_utc: CarbonImmutable}
     */
    public function currentFootballDay(string $timezone = self::DEFAULT_TIMEZONE): array
    {
        $current = CarbonImmutable::now($timezone);
        $footballDate = $current->hour < self::START_HOUR
            ? $current->subDay()->toDateString()
            : $current->toDateString();

        return $this->rangeForDate($footballDate, $timezone);
    }

    /**
     * @return array{football_date: string, start: CarbonImmutable, end: CarbonImmutable, start_utc: CarbonImmutable, end_utc: CarbonImmutable}
     */
    public function rangeForDate(CarbonInterface|string|null $date = null, string $timezone = self::DEFAULT_TIMEZONE): array
    {
        $footballDate = $this->asFootballDate($date, $timezone);
        $start = CarbonImmutable::parse($footballDate, $timezone)->setTime(self::START_HOUR, 0);
        $end = $start->addDay()->setTime(5, 59, 59);

        return [
            'football_date' => $footballDate,
            'start' => $start,
            'end' => $end,
            'start_utc' => $start->utc(),
            'end_utc' => $end->utc(),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function todayQueryRangeUtc(): array
    {
        $range = $this->currentFootballDay();

        return [$range['start_utc'], $range['end_utc']];
    }

    private function asFootballDate(CarbonInterface|string|null $date, string $timezone): string
    {
        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->setTimezone($timezone)->toDateString();
        }

        if (filled($date)) {
            return CarbonImmutable::parse((string) $date, $timezone)->toDateString();
        }

        return CarbonImmutable::now($timezone)->toDateString();
    }
}

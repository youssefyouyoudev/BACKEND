<?php

namespace Database\Seeders;

use App\Models\WorldCupMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WorldCup2026GroupStageSeeder extends Seeder
{
    private const FIFA_SOURCE_URL = 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/match-schedule-fixtures-results-teams-stadiums';

    public function run(): void
    {
        foreach ($this->matches() as [$number, $group, $date, $time, $utcOffset, $home, $away, $stadium]) {
            [$venue, $city] = array_map('trim', explode(',', $stadium, 2));
            $timezone = $this->timezoneForCity($city);
            $country = $this->countryForCity($city);
            $offset = sprintf('%+03d:00', $utcOffset);
            $localKickoff = Carbon::createFromFormat('Y-m-d H:i P', "{$date} {$time} {$offset}");
            $kickoffAt = $localKickoff->copy()->utc();
            $moroccoKickoff = $kickoffAt->copy()->setTimezone('Africa/Casablanca');

            $match = WorldCupMatch::query()->firstOrNew(['match_number' => $number]);
            $isNew = ! $match->exists;

            $match->fill([
                'competition' => 'FIFA World Cup 2026',
                'stage' => 'Group Stage',
                'group_name' => "Group {$group}",
                'home_team' => $home,
                'away_team' => $away,
                'venue' => $venue,
                'city' => $city,
                'country' => $country,
                'kickoff_at' => $kickoffAt,
                'morocco_kickoff_at' => $moroccoKickoff->format('Y-m-d H:i:s'),
                'local_kickoff_at' => "{$date} {$time}:00",
                'local_timezone' => $timezone,
                'source_name' => $number === 1 ? 'FIFA + attached MENA broadcast guide' : 'FIFA',
                'source_url' => self::FIFA_SOURCE_URL,
                'sort_order' => $number,
            ]);

            if ($isNew && $number === 1) {
                $match->fill([
                    'channel_name_manual' => 'beIN SPORTS HD / beIN SPORTS MAX 1 / MAX 2',
                    'commentator' => 'Hafid Derradji; Ali Saïd Al-Kaabi',
                    'broadcaster' => 'beIN SPORTS MENA',
                ]);
            }

            $match->save();
        }
    }

    private function timezoneForCity(string $city): string
    {
        return match ($city) {
            'Mexico City', 'Zapopan' => 'America/Mexico_City',
            'Guadalupe' => 'America/Monterrey',
            'Toronto' => 'America/Toronto',
            'Inglewood', 'Santa Clara' => 'America/Los_Angeles',
            'Vancouver' => 'America/Vancouver',
            'Houston', 'Arlington', 'Kansas City' => 'America/Chicago',
            default => 'America/New_York',
        };
    }

    private function countryForCity(string $city): string
    {
        return match ($city) {
            'Mexico City', 'Zapopan', 'Guadalupe' => 'Mexico',
            'Toronto', 'Vancouver' => 'Canada',
            default => 'United States',
        };
    }

    /**
     * Extracted from the attached wc_2026_mena_group_stage.html file.
     *
     * @return array<int, array{int, string, string, string, int, string, string, string}>
     */
    private function matches(): array
    {
        return [
            [1, "A", "2026-06-11", "13:00", -6, "Mexico", "South Africa", "Estadio Azteca, Mexico City"],
            [2, "A", "2026-06-11", "20:00", -6, "South Korea", "Czech Republic", "Estadio Akron, Zapopan"],
            [3, "B", "2026-06-12", "15:00", -4, "Canada", "Bosnia and Herzegovina", "BMO Field, Toronto"],
            [4, "D", "2026-06-12", "18:00", -7, "United States", "Paraguay", "SoFi Stadium, Inglewood"],
            [5, "C", "2026-06-13", "21:00", -4, "Haiti", "Scotland", "Gillette Stadium, Foxborough"],
            [6, "D", "2026-06-13", "21:00", -7, "Australia", "Turkey", "BC Place, Vancouver"],
            [7, "C", "2026-06-13", "18:00", -4, "Brazil", "Morocco", "MetLife Stadium, East Rutherford"],
            [8, "B", "2026-06-13", "12:00", -7, "Qatar", "Switzerland", "Levi's Stadium, Santa Clara"],
            [9, "E", "2026-06-14", "19:00", -4, "Ivory Coast", "Ecuador", "Lincoln Financial Field, Philadelphia"],
            [10, "E", "2026-06-14", "12:00", -5, "Germany", "Curaçao", "NRG Stadium, Houston"],
            [11, "F", "2026-06-14", "15:00", -5, "Netherlands", "Japan", "AT&T Stadium, Arlington"],
            [12, "F", "2026-06-14", "20:00", -6, "Sweden", "Tunisia", "Estadio BBVA, Guadalupe"],
            [13, "H", "2026-06-15", "18:00", -4, "Saudi Arabia", "Uruguay", "Hard Rock Stadium, Miami Gardens"],
            [14, "H", "2026-06-15", "12:00", -4, "Spain", "Cape Verde", "Mercedes-Benz Stadium, Atlanta"],
            [15, "G", "2026-06-15", "18:00", -7, "Iran", "New Zealand", "SoFi Stadium, Inglewood"],
            [16, "G", "2026-06-15", "12:00", -7, "Belgium", "Egypt", "Lumen Field, Seattle"],
            [17, "I", "2026-06-16", "15:00", -4, "France", "Senegal", "MetLife Stadium, East Rutherford"],
            [18, "I", "2026-06-16", "18:00", -4, "Iraq", "Norway", "Gillette Stadium, Foxborough"],
            [19, "J", "2026-06-16", "20:00", -5, "Argentina", "Algeria", "Arrowhead Stadium, Kansas City"],
            [20, "J", "2026-06-16", "21:00", -7, "Austria", "Jordan", "Levi's Stadium, Santa Clara"],
            [21, "L", "2026-06-17", "19:00", -4, "Ghana", "Panama", "Toronto Stadium, Toronto"],
            [22, "L", "2026-06-17", "15:00", -5, "England", "Croatia", "AT&T Stadium, Arlington"],
            [23, "K", "2026-06-17", "12:00", -5, "Portugal", "DR Congo", "NRG Stadium, Houston"],
            [24, "K", "2026-06-17", "20:00", -6, "Uzbekistan", "Colombia", "Estadio Azteca, Mexico City"],
            [25, "A", "2026-06-18", "12:00", -4, "Czech Republic", "South Africa", "Mercedes-Benz Stadium, Atlanta"],
            [26, "B", "2026-06-18", "12:00", -7, "Switzerland", "Bosnia and Herzegovina", "SoFi Stadium, Inglewood"],
            [27, "B", "2026-06-18", "15:00", -7, "Canada", "Qatar", "BC Place, Vancouver"],
            [28, "A", "2026-06-18", "19:00", -6, "Mexico", "South Korea", "Estadio Akron, Zapopan"],
            [29, "C", "2026-06-19", "20:30", -4, "Brazil", "Haiti", "Lincoln Financial Field, Philadelphia"],
            [30, "C", "2026-06-19", "18:00", -4, "Scotland", "Morocco", "Gillette Stadium, Foxborough"],
            [31, "D", "2026-06-19", "20:00", -7, "Turkey", "Paraguay", "Levi's Stadium, Santa Clara"],
            [32, "D", "2026-06-19", "12:00", -7, "United States", "Australia", "Lumen Field, Seattle"],
            [33, "E", "2026-06-20", "16:00", -4, "Germany", "Ivory Coast", "BMO Field, Toronto"],
            [34, "E", "2026-06-20", "19:00", -5, "Ecuador", "Curaçao", "Arrowhead Stadium, Kansas City"],
            [35, "F", "2026-06-20", "12:00", -5, "Netherlands", "Sweden", "NRG Stadium, Houston"],
            [36, "F", "2026-06-20", "22:00", -6, "Tunisia", "Japan", "Estadio BBVA, Guadalupe"],
            [37, "H", "2026-06-21", "18:00", -4, "Uruguay", "Cape Verde", "Hard Rock Stadium, Miami Gardens"],
            [38, "H", "2026-06-21", "12:00", -4, "Spain", "Saudi Arabia", "Mercedes-Benz Stadium, Atlanta"],
            [39, "G", "2026-06-21", "12:00", -7, "Belgium", "Iran", "SoFi Stadium, Inglewood"],
            [40, "G", "2026-06-21", "18:00", -7, "New Zealand", "Egypt", "BC Place, Vancouver"],
            [41, "I", "2026-06-22", "20:00", -4, "Norway", "Senegal", "MetLife Stadium, East Rutherford"],
            [42, "I", "2026-06-22", "17:00", -4, "France", "Iraq", "Lincoln Financial Field, Philadelphia"],
            [43, "J", "2026-06-22", "12:00", -5, "Argentina", "Austria", "AT&T Stadium, Arlington"],
            [44, "J", "2026-06-22", "20:00", -7, "Jordan", "Algeria", "Levi's Stadium, Santa Clara"],
            [45, "L", "2026-06-23", "16:00", -4, "England", "Ghana", "Gillette Stadium, Foxborough"],
            [46, "L", "2026-06-23", "19:00", -4, "Panama", "Croatia", "Toronto Stadium, Toronto"],
            [47, "K", "2026-06-23", "12:00", -5, "Portugal", "Uzbekistan", "NRG Stadium, Houston"],
            [48, "K", "2026-06-23", "20:00", -6, "Colombia", "DR Congo", "Estadio Akron, Zapopan"],
            [49, "C", "2026-06-24", "18:00", -4, "Scotland", "Brazil", "Hard Rock Stadium, Miami Gardens"],
            [50, "C", "2026-06-24", "18:00", -4, "Morocco", "Haiti", "Mercedes-Benz Stadium, Atlanta"],
            [51, "B", "2026-06-24", "12:00", -7, "Switzerland", "Canada", "BC Place, Vancouver"],
            [52, "B", "2026-06-24", "12:00", -7, "Bosnia and Herzegovina", "Qatar", "Lumen Field, Seattle"],
            [53, "A", "2026-06-24", "19:00", -6, "Czech Republic", "Mexico", "Estadio Azteca, Mexico City"],
            [54, "A", "2026-06-24", "19:00", -6, "South Africa", "South Korea", "Estadio BBVA, Guadalupe"],
            [55, "E", "2026-06-25", "16:00", -4, "Curaçao", "Ivory Coast", "Lincoln Financial Field, Philadelphia"],
            [56, "E", "2026-06-25", "16:00", -4, "Ecuador", "Germany", "MetLife Stadium, East Rutherford"],
            [57, "F", "2026-06-25", "18:00", -5, "Japan", "Sweden", "AT&T Stadium, Arlington"],
            [58, "F", "2026-06-25", "18:00", -5, "Tunisia", "Netherlands", "Arrowhead Stadium, Kansas City"],
            [59, "D", "2026-06-25", "19:00", -7, "Turkey", "United States", "SoFi Stadium, Inglewood"],
            [60, "D", "2026-06-25", "19:00", -7, "Paraguay", "Australia", "Levi's Stadium, Santa Clara"],
            [61, "I", "2026-06-26", "15:00", -4, "Norway", "France", "Gillette Stadium, Foxborough"],
            [62, "I", "2026-06-26", "15:00", -4, "Senegal", "Iraq", "BMO Field, Toronto"],
            [63, "G", "2026-06-26", "20:00", -7, "Egypt", "Iran", "Lumen Field, Seattle"],
            [64, "G", "2026-06-26", "20:00", -7, "New Zealand", "Belgium", "BC Place, Vancouver"],
            [65, "H", "2026-06-26", "19:00", -5, "Cape Verde", "Saudi Arabia", "NRG Stadium, Houston"],
            [66, "H", "2026-06-26", "18:00", -6, "Uruguay", "Spain", "Estadio Akron, Zapopan"],
            [67, "L", "2026-06-27", "17:00", -4, "Panama", "England", "MetLife Stadium, East Rutherford"],
            [68, "L", "2026-06-27", "17:00", -4, "Croatia", "Ghana", "Lincoln Financial Field, Philadelphia"],
            [69, "J", "2026-06-27", "21:00", -5, "Algeria", "Austria", "Arrowhead Stadium, Kansas City"],
            [70, "J", "2026-06-27", "21:00", -5, "Jordan", "Argentina", "AT&T Stadium, Arlington"],
            [71, "K", "2026-06-27", "19:30", -4, "Colombia", "Portugal", "Hard Rock Stadium, Miami Gardens"],
            [72, "K", "2026-06-27", "19:30", -4, "DR Congo", "Uzbekistan", "Mercedes-Benz Stadium, Atlanta"],
        ];
    }
}

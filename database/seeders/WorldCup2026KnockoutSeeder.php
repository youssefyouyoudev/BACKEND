<?php

namespace Database\Seeders;

use App\Models\WorldCupMatch;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorldCup2026KnockoutSeeder extends Seeder
{
    private const SOURCE_NAME = 'FIFA World Cup 2026 knockout schedule';

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->matches() as $data) {
                $localKickoff = CarbonImmutable::parse($data['kickoff_local'], $data['timezone']);
                $kickoffAt = $localKickoff->utc();
                $moroccoKickoff = $kickoffAt->setTimezone(WorldCupMatch::MOROCCO_TIMEZONE);

                $match = WorldCupMatch::query()->firstOrNew(['match_number' => $data['match_number']]);
                $isNew = ! $match->exists;

                $match->fill([
                    'competition' => 'FIFA World Cup 2026',
                    'stage' => $data['stage'],
                    'stage_label' => $data['stage_label'],
                    'group_name' => null,
                    'home_team' => $data['home_team'],
                    'away_team' => $data['away_team'],
                    'home_placeholder' => $data['home_placeholder'],
                    'away_placeholder' => $data['away_placeholder'],
                    'venue' => $data['venue'],
                    'city' => $data['city'],
                    'country' => $data['country'],
                    'kickoff_at' => $kickoffAt,
                    'morocco_kickoff_at' => $moroccoKickoff->format('Y-m-d H:i:s'),
                    'local_kickoff_at' => $data['kickoff_local'],
                    'local_timezone' => $data['timezone'],
                    'broadcast_status' => 'scheduled',
                    'source_name' => self::SOURCE_NAME,
                    'sort_order' => $data['match_number'],
                    'is_knockout' => true,
                ]);

                if ($isNew) {
                    $match->fill([
                        'channel_name_manual' => $data['channel'],
                        'commentator' => $data['commentator'],
                        'stream_links' => $data['stream_links'],
                    ]);
                }

                $match->save();
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matches(): array
    {
        return [
            ['match_number' => 73, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'South Africa', 'away_team' => 'Canada', 'home_placeholder' => '2A', 'away_placeholder' => '2B', 'kickoff_local' => '2026-06-28 12:00:00', 'timezone' => 'America/Los_Angeles', 'venue' => 'SoFi Stadium', 'city' => 'Inglewood', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 74, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Germany', 'away_team' => 'Paraguay', 'home_placeholder' => '1E', 'away_placeholder' => '3ABCDF', 'kickoff_local' => '2026-06-29 16:30:00', 'timezone' => 'America/New_York', 'venue' => 'Gillette Stadium', 'city' => 'Foxborough', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 75, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Netherlands', 'away_team' => 'Morocco', 'home_placeholder' => '1F', 'away_placeholder' => '2C', 'kickoff_local' => '2026-06-29 19:00:00', 'timezone' => 'America/Monterrey', 'venue' => 'Estadio BBVA', 'city' => 'Guadalupe', 'country' => 'Mexico', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 76, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Brazil', 'away_team' => 'Japan', 'home_placeholder' => '1C', 'away_placeholder' => '2F', 'kickoff_local' => '2026-06-29 12:00:00', 'timezone' => 'America/Chicago', 'venue' => 'NRG Stadium', 'city' => 'Houston', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 77, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'France', 'away_team' => 'Sweden', 'home_placeholder' => '1I', 'away_placeholder' => '3CDFGH', 'kickoff_local' => '2026-06-30 17:00:00', 'timezone' => 'America/New_York', 'venue' => 'MetLife Stadium', 'city' => 'East Rutherford', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 78, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Ivory Coast', 'away_team' => 'Norway', 'home_placeholder' => '2E', 'away_placeholder' => '2I', 'kickoff_local' => '2026-06-30 12:00:00', 'timezone' => 'America/Chicago', 'venue' => 'AT&T Stadium', 'city' => 'Arlington', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 79, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Mexico', 'away_team' => 'Ecuador', 'home_placeholder' => '1A', 'away_placeholder' => '3CEFHI', 'kickoff_local' => '2026-06-30 19:00:00', 'timezone' => 'America/Mexico_City', 'venue' => 'Estadio Azteca', 'city' => 'Mexico City', 'country' => 'Mexico', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 80, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'England', 'away_team' => 'DR Congo', 'home_placeholder' => '1L', 'away_placeholder' => '3EHIJK', 'kickoff_local' => '2026-07-01 12:00:00', 'timezone' => 'America/New_York', 'venue' => 'Mercedes-Benz Stadium', 'city' => 'Atlanta', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 81, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'United States', 'away_team' => 'Bosnia and Herzegovina', 'home_placeholder' => '1D', 'away_placeholder' => '3BEFIJ', 'kickoff_local' => '2026-07-01 17:00:00', 'timezone' => 'America/Los_Angeles', 'venue' => "Levi's Stadium", 'city' => 'Santa Clara', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 82, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Belgium', 'away_team' => 'Senegal', 'home_placeholder' => '1G', 'away_placeholder' => '3AEHIJ', 'kickoff_local' => '2026-07-01 13:00:00', 'timezone' => 'America/Los_Angeles', 'venue' => 'Lumen Field', 'city' => 'Seattle', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 83, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Portugal', 'away_team' => 'Croatia', 'home_placeholder' => '2K', 'away_placeholder' => '2L', 'kickoff_local' => '2026-07-02 19:00:00', 'timezone' => 'America/Toronto', 'venue' => 'BMO Field', 'city' => 'Toronto', 'country' => 'Canada', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 84, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Spain', 'away_team' => 'Austria', 'home_placeholder' => '1H', 'away_placeholder' => '2J', 'kickoff_local' => '2026-07-02 12:00:00', 'timezone' => 'America/Los_Angeles', 'venue' => 'SoFi Stadium', 'city' => 'Inglewood', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 85, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Switzerland', 'away_team' => 'Algeria', 'home_placeholder' => '1B', 'away_placeholder' => '3EFGIJ', 'kickoff_local' => '2026-07-02 20:00:00', 'timezone' => 'America/Vancouver', 'venue' => 'BC Place', 'city' => 'Vancouver', 'country' => 'Canada', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 86, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Argentina', 'away_team' => 'Cape Verde', 'home_placeholder' => '1J', 'away_placeholder' => '2H', 'kickoff_local' => '2026-07-03 18:00:00', 'timezone' => 'America/New_York', 'venue' => 'Hard Rock Stadium', 'city' => 'Miami Gardens', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 87, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Colombia', 'away_team' => 'Ghana', 'home_placeholder' => '1K', 'away_placeholder' => '3DEIJL', 'kickoff_local' => '2026-07-03 20:30:00', 'timezone' => 'America/Chicago', 'venue' => 'Arrowhead Stadium', 'city' => 'Kansas City', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 88, 'stage' => 'round_of_32', 'stage_label' => 'Round of 32', 'home_team' => 'Australia', 'away_team' => 'Egypt', 'home_placeholder' => '2D', 'away_placeholder' => '2G', 'kickoff_local' => '2026-07-03 13:00:00', 'timezone' => 'America/Chicago', 'venue' => 'AT&T Stadium', 'city' => 'Arlington', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 89, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 74', 'away_team' => 'Winner Match 77', 'home_placeholder' => 'W74', 'away_placeholder' => 'W77', 'kickoff_local' => '2026-07-04 17:00:00', 'timezone' => 'America/New_York', 'venue' => 'Lincoln Financial Field', 'city' => 'Philadelphia', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 90, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 73', 'away_team' => 'Winner Match 75', 'home_placeholder' => 'W73', 'away_placeholder' => 'W75', 'kickoff_local' => '2026-07-04 12:00:00', 'timezone' => 'America/Chicago', 'venue' => 'NRG Stadium', 'city' => 'Houston', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 91, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 76', 'away_team' => 'Winner Match 78', 'home_placeholder' => 'W76', 'away_placeholder' => 'W78', 'kickoff_local' => '2026-07-05 16:00:00', 'timezone' => 'America/New_York', 'venue' => 'MetLife Stadium', 'city' => 'East Rutherford', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 92, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 79', 'away_team' => 'Winner Match 80', 'home_placeholder' => 'W79', 'away_placeholder' => 'W80', 'kickoff_local' => '2026-07-05 18:00:00', 'timezone' => 'America/Mexico_City', 'venue' => 'Estadio Azteca', 'city' => 'Mexico City', 'country' => 'Mexico', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 93, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 83', 'away_team' => 'Winner Match 84', 'home_placeholder' => 'W83', 'away_placeholder' => 'W84', 'kickoff_local' => '2026-07-06 14:00:00', 'timezone' => 'America/Chicago', 'venue' => 'AT&T Stadium', 'city' => 'Arlington', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 94, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 81', 'away_team' => 'Winner Match 82', 'home_placeholder' => 'W81', 'away_placeholder' => 'W82', 'kickoff_local' => '2026-07-06 17:00:00', 'timezone' => 'America/Los_Angeles', 'venue' => 'Lumen Field', 'city' => 'Seattle', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 95, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 86', 'away_team' => 'Winner Match 88', 'home_placeholder' => 'W86', 'away_placeholder' => 'W88', 'kickoff_local' => '2026-07-07 12:00:00', 'timezone' => 'America/New_York', 'venue' => 'Mercedes-Benz Stadium', 'city' => 'Atlanta', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 96, 'stage' => 'round_of_16', 'stage_label' => 'Round of 16', 'home_team' => 'Winner Match 85', 'away_team' => 'Winner Match 87', 'home_placeholder' => 'W85', 'away_placeholder' => 'W87', 'kickoff_local' => '2026-07-07 13:00:00', 'timezone' => 'America/Vancouver', 'venue' => 'BC Place', 'city' => 'Vancouver', 'country' => 'Canada', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 97, 'stage' => 'quarter_final', 'stage_label' => 'Quarter-final', 'home_team' => 'Winner Match 89', 'away_team' => 'Winner Match 90', 'home_placeholder' => 'W89', 'away_placeholder' => 'W90', 'kickoff_local' => '2026-07-09 16:00:00', 'timezone' => 'America/New_York', 'venue' => 'Gillette Stadium', 'city' => 'Foxborough', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 98, 'stage' => 'quarter_final', 'stage_label' => 'Quarter-final', 'home_team' => 'Winner Match 93', 'away_team' => 'Winner Match 94', 'home_placeholder' => 'W93', 'away_placeholder' => 'W94', 'kickoff_local' => '2026-07-10 12:00:00', 'timezone' => 'America/Los_Angeles', 'venue' => 'SoFi Stadium', 'city' => 'Inglewood', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 99, 'stage' => 'quarter_final', 'stage_label' => 'Quarter-final', 'home_team' => 'Winner Match 91', 'away_team' => 'Winner Match 92', 'home_placeholder' => 'W91', 'away_placeholder' => 'W92', 'kickoff_local' => '2026-07-11 17:00:00', 'timezone' => 'America/New_York', 'venue' => 'Hard Rock Stadium', 'city' => 'Miami Gardens', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 100, 'stage' => 'quarter_final', 'stage_label' => 'Quarter-final', 'home_team' => 'Winner Match 95', 'away_team' => 'Winner Match 96', 'home_placeholder' => 'W95', 'away_placeholder' => 'W96', 'kickoff_local' => '2026-07-11 20:00:00', 'timezone' => 'America/Chicago', 'venue' => 'Arrowhead Stadium', 'city' => 'Kansas City', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 101, 'stage' => 'semi_final', 'stage_label' => 'Semi-final', 'home_team' => 'Winner Match 97', 'away_team' => 'Winner Match 98', 'home_placeholder' => 'W97', 'away_placeholder' => 'W98', 'kickoff_local' => '2026-07-14 14:00:00', 'timezone' => 'America/Chicago', 'venue' => 'AT&T Stadium', 'city' => 'Arlington', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 102, 'stage' => 'semi_final', 'stage_label' => 'Semi-final', 'home_team' => 'Winner Match 99', 'away_team' => 'Winner Match 100', 'home_placeholder' => 'W99', 'away_placeholder' => 'W100', 'kickoff_local' => '2026-07-15 15:00:00', 'timezone' => 'America/New_York', 'venue' => 'Mercedes-Benz Stadium', 'city' => 'Atlanta', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 103, 'stage' => 'third_place', 'stage_label' => 'Third-place match', 'home_team' => 'Loser Match 101', 'away_team' => 'Loser Match 102', 'home_placeholder' => 'L101', 'away_placeholder' => 'L102', 'kickoff_local' => '2026-07-18 17:00:00', 'timezone' => 'America/New_York', 'venue' => 'Hard Rock Stadium', 'city' => 'Miami Gardens', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
            ['match_number' => 104, 'stage' => 'final', 'stage_label' => 'Final', 'home_team' => 'Winner Match 101', 'away_team' => 'Winner Match 102', 'home_placeholder' => 'W101', 'away_placeholder' => 'W102', 'kickoff_local' => '2026-07-19 15:00:00', 'timezone' => 'America/New_York', 'venue' => 'MetLife Stadium', 'city' => 'East Rutherford', 'country' => 'USA', 'channel' => null, 'commentator' => null, 'stream_links' => []],
        ];
    }
}

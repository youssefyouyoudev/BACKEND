<?php

namespace App\Console\Commands;

use App\Models\WorldCupMatch;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AutoEndOldMatchesCommand extends Command
{
    protected $signature = 'matches:auto-end-old';

    protected $description = 'Automatically mark old football matches as ended after their three-hour match window.';

    public function handle(): int
    {
        $now = CarbonImmutable::now(WorldCupMatch::MOROCCO_TIMEZONE);
        $cutoffUtc = $now->subHours(3)->utc();

        $updated = WorldCupMatch::query()
            ->whereNotNull('kickoff_at')
            ->where('kickoff_at', '<=', $cutoffUtc)
            ->whereIn('broadcast_status', WorldCupMatch::AUTO_END_STATUSES)
            ->update([
                'broadcast_status' => WorldCupMatch::STATUS_ENDED,
                'is_live_link_enabled' => false,
                'ended_at' => $now->utc(),
                'status_updated_by' => 'automatic',
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            Cache::forget('home:matches:today');
            Cache::forget('home:matches:upcoming');
        }

        $this->info("Auto-ended {$updated} old matches.");

        return self::SUCCESS;
    }
}

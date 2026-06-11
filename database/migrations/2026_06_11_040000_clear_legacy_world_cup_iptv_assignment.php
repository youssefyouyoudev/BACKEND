<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('world_cup_matches')
            ->whereNotNull('selected_iptv_item_id')
            ->update(['selected_iptv_item_id' => null]);
    }

    public function down(): void
    {
        DB::table('world_cup_match_iptv_item')
            ->orderBy('id')
            ->get()
            ->groupBy('world_cup_match_id')
            ->each(function ($assignments, int $matchId): void {
                DB::table('world_cup_matches')
                    ->where('id', $matchId)
                    ->update(['selected_iptv_item_id' => $assignments->first()->iptv_item_id]);
            });
    }
};

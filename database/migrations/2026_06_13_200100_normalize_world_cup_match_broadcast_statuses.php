<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('world_cup_matches') || ! Schema::hasColumn('world_cup_matches', 'broadcast_status')) {
            return;
        }

        DB::table('world_cup_matches')
            ->where('broadcast_status', 'confirmed')
            ->update(['broadcast_status' => 'scheduled']);

        DB::table('world_cup_matches')
            ->where('broadcast_status', 'finished')
            ->update(['broadcast_status' => 'ended']);

        DB::table('world_cup_matches')
            ->where('broadcast_status', 'postponed')
            ->update(['broadcast_status' => 'cancelled']);
    }

    public function down(): void
    {
        // Keep normalized statuses; reverting would reintroduce values no longer exposed in admin.
    }
};

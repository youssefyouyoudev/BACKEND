<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_cup_match_iptv_item', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('world_cup_match_id')
                ->constrained('world_cup_matches')
                ->cascadeOnDelete();
            $table->foreignId('iptv_item_id')
                ->constrained('iptv_items')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['world_cup_match_id', 'iptv_item_id'], 'wc_match_iptv_unique');
        });

        DB::table('world_cup_matches')
            ->whereNotNull('selected_iptv_item_id')
            ->orderBy('id')
            ->each(function (object $match): void {
                DB::table('world_cup_match_iptv_item')->insertOrIgnore([
                    'world_cup_match_id' => $match->id,
                    'iptv_item_id' => $match->selected_iptv_item_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_cup_match_iptv_item');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('world_cup_matches')) {
            return;
        }

        Schema::table('world_cup_matches', function (Blueprint $table): void {
            if (! Schema::hasColumn('world_cup_matches', 'player_type')) {
                $table->string('player_type', 32)->nullable()->default('iframe')->after('live_url_manual');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: this preserves match player settings on rollback.
    }
};

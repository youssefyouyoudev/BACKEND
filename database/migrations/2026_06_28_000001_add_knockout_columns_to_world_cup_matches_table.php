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
            if (! Schema::hasColumn('world_cup_matches', 'stage_label')) {
                $table->string('stage_label', 80)->nullable()->after('stage');
            }

            if (! Schema::hasColumn('world_cup_matches', 'home_placeholder')) {
                $table->string('home_placeholder', 80)->nullable()->after('away_team');
            }

            if (! Schema::hasColumn('world_cup_matches', 'away_placeholder')) {
                $table->string('away_placeholder', 80)->nullable()->after('home_placeholder');
            }

            if (! Schema::hasColumn('world_cup_matches', 'stream_links')) {
                $table->json('stream_links')->nullable()->after('commentator');
            }

            if (! Schema::hasColumn('world_cup_matches', 'is_knockout')) {
                $table->boolean('is_knockout')->default(false)->index()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: these nullable columns may contain admin-entered match data.
    }
};

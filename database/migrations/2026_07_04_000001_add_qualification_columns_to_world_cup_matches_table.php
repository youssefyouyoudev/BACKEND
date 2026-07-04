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
            if (! Schema::hasColumn('world_cup_matches', 'qualified_team')) {
                $table->string('qualified_team', 120)->nullable()->after('broadcast_status');
            }

            if (! Schema::hasColumn('world_cup_matches', 'eliminated_team')) {
                $table->string('eliminated_team', 120)->nullable()->after('qualified_team');
            }

            if (! Schema::hasColumn('world_cup_matches', 'qualified_side')) {
                $table->string('qualified_side', 12)->nullable()->after('eliminated_team');
            }

            if (! Schema::hasColumn('world_cup_matches', 'status')) {
                $table->string('status', 24)->default('scheduled')->index()->after('qualified_side');
            }

            if (! Schema::hasColumn('world_cup_matches', 'advanced_at')) {
                $table->timestamp('advanced_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('world_cup_matches', 'is_knockout')) {
                $table->boolean('is_knockout')->default(false)->index()->after('advanced_at');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: qualification data may have been entered by admins.
    }
};

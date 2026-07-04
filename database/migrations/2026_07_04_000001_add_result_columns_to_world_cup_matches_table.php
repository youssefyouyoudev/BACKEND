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
            if (! Schema::hasColumn('world_cup_matches', 'home_score')) {
                $table->unsignedSmallInteger('home_score')->nullable()->after('broadcast_status');
            }

            if (! Schema::hasColumn('world_cup_matches', 'away_score')) {
                $table->unsignedSmallInteger('away_score')->nullable()->after('home_score');
            }

            if (! Schema::hasColumn('world_cup_matches', 'home_penalties')) {
                $table->unsignedSmallInteger('home_penalties')->nullable()->after('away_score');
            }

            if (! Schema::hasColumn('world_cup_matches', 'away_penalties')) {
                $table->unsignedSmallInteger('away_penalties')->nullable()->after('home_penalties');
            }

            if (! Schema::hasColumn('world_cup_matches', 'winner_team')) {
                $table->string('winner_team', 120)->nullable()->after('away_penalties');
            }

            if (! Schema::hasColumn('world_cup_matches', 'loser_team')) {
                $table->string('loser_team', 120)->nullable()->after('winner_team');
            }

            if (! Schema::hasColumn('world_cup_matches', 'winner_source')) {
                $table->string('winner_source', 24)->nullable()->after('loser_team');
            }

            if (! Schema::hasColumn('world_cup_matches', 'loser_source')) {
                $table->string('loser_source', 24)->nullable()->after('winner_source');
            }

            if (! Schema::hasColumn('world_cup_matches', 'winner_match_number')) {
                $table->unsignedInteger('winner_match_number')->nullable()->after('loser_source');
            }

            if (! Schema::hasColumn('world_cup_matches', 'loser_match_number')) {
                $table->unsignedInteger('loser_match_number')->nullable()->after('winner_match_number');
            }

            if (! Schema::hasColumn('world_cup_matches', 'status')) {
                $table->string('status', 24)->default('scheduled')->index()->after('loser_match_number');
            }

            if (! Schema::hasColumn('world_cup_matches', 'played_at')) {
                $table->timestamp('played_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: result data may have been entered by admins.
    }
};

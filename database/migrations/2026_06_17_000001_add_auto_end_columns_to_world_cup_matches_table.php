<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('world_cup_matches', function (Blueprint $table): void {
            if (! Schema::hasColumn('world_cup_matches', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('broadcast_status')->index();
            }

            if (! Schema::hasColumn('world_cup_matches', 'status_updated_by')) {
                $table->string('status_updated_by', 40)->nullable()->after('ended_at')->index();
            }

            if (! Schema::hasColumn('world_cup_matches', 'slug')) {
                $table->string('slug')->nullable()->after('match_number')->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('world_cup_matches', function (Blueprint $table): void {
            if (Schema::hasColumn('world_cup_matches', 'status_updated_by')) {
                $table->dropColumn('status_updated_by');
            }

            if (Schema::hasColumn('world_cup_matches', 'ended_at')) {
                $table->dropColumn('ended_at');
            }

            if (Schema::hasColumn('world_cup_matches', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('world_cup_matches', function (Blueprint $table): void {
            $table->dateTime('watch_opens_at')->nullable()->after('local_timezone');
            $table->dateTime('watch_expires_at')->nullable()->after('watch_opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('world_cup_matches', function (Blueprint $table): void {
            $table->dropColumn(['watch_opens_at', 'watch_expires_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('world_cup_matches', function (Blueprint $table): void {
            $table->foreignId('selected_iptv_item_id')
                ->nullable()
                ->after('selected_channel_id')
                ->constrained('iptv_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('world_cup_matches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('selected_iptv_item_id');
        });
    }
};

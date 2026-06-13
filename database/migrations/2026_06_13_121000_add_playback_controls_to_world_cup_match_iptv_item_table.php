<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('world_cup_match_iptv_item', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('iptv_item_id');
            $table->unsignedInteger('priority')->default(0)->after('is_active');
            $table->dateTime('starts_at')->nullable()->after('priority');
            $table->dateTime('expires_at')->nullable()->after('starts_at');
            $table->index(['world_cup_match_id', 'is_active', 'priority'], 'wc_match_stream_active_priority');
        });
    }

    public function down(): void
    {
        Schema::table('world_cup_match_iptv_item', function (Blueprint $table): void {
            $table->dropIndex('wc_match_stream_active_priority');
            $table->dropColumn(['is_active', 'priority', 'starts_at', 'expires_at']);
        });
    }
};

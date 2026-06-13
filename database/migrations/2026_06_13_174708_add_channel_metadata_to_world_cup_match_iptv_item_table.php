<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('world_cup_match_iptv_item', function (Blueprint $table) {
            $table->string('channel_name', 160)->nullable()->after('priority');
            $table->string('stream_title', 160)->nullable()->after('channel_name');
            $table->string('stream_type', 32)->nullable()->after('stream_title');
            $table->string('quality', 16)->nullable()->after('stream_type');
            $table->string('language', 60)->nullable()->after('quality');
            $table->string('commentator', 120)->nullable()->after('language');
            $table->string('server_label', 80)->nullable()->after('commentator');
            $table->boolean('is_recommended')->default(false)->after('server_label');
            $table->string('health_status', 20)->nullable()->after('is_recommended');
            $table->dateTime('last_checked_at')->nullable()->after('health_status');
            $table->index(
                ['world_cup_match_id', 'is_active', 'is_recommended', 'priority'],
                'wc_match_stream_playback_order'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('world_cup_match_iptv_item', function (Blueprint $table) {
            $table->dropIndex('wc_match_stream_playback_order');
            $table->dropColumn([
                'channel_name',
                'stream_title',
                'stream_type',
                'quality',
                'language',
                'commentator',
                'server_label',
                'is_recommended',
                'health_status',
                'last_checked_at',
            ]);
        });
    }
};

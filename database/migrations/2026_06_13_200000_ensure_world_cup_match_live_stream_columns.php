<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('world_cup_matches')) {
            Schema::table('world_cup_matches', function (Blueprint $table): void {
                if (! Schema::hasColumn('world_cup_matches', 'selected_channel_id')) {
                    $table->unsignedBigInteger('selected_channel_id')->nullable()->index();
                }

                if (! Schema::hasColumn('world_cup_matches', 'selected_iptv_item_id')) {
                    $table->unsignedBigInteger('selected_iptv_item_id')->nullable()->index();
                }

                if (! Schema::hasColumn('world_cup_matches', 'channel_name_manual')) {
                    $table->string('channel_name_manual', 120)->nullable();
                }

                if (! Schema::hasColumn('world_cup_matches', 'broadcaster')) {
                    $table->string('broadcaster', 120)->nullable();
                }

                if (! Schema::hasColumn('world_cup_matches', 'live_url_manual')) {
                    $table->text('live_url_manual')->nullable();
                }

                if (! Schema::hasColumn('world_cup_matches', 'use_manual_live_url')) {
                    $table->boolean('use_manual_live_url')->default(false);
                }

                if (! Schema::hasColumn('world_cup_matches', 'is_live_link_enabled')) {
                    $table->boolean('is_live_link_enabled')->default(false);
                }

                if (! Schema::hasColumn('world_cup_matches', 'broadcast_status')) {
                    $table->string('broadcast_status', 24)->default('to_confirm')->index();
                }

                if (! Schema::hasColumn('world_cup_matches', 'watch_opens_at')) {
                    $table->timestamp('watch_opens_at')->nullable();
                }

                if (! Schema::hasColumn('world_cup_matches', 'watch_expires_at')) {
                    $table->timestamp('watch_expires_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('world_cup_match_iptv_item')) {
            Schema::table('world_cup_match_iptv_item', function (Blueprint $table): void {
                if (! Schema::hasColumn('world_cup_match_iptv_item', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'priority')) {
                    $table->unsignedInteger('priority')->default(1);
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'channel_name')) {
                    $table->string('channel_name', 160)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'stream_title')) {
                    $table->string('stream_title', 160)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'stream_type')) {
                    $table->string('stream_type', 32)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'quality')) {
                    $table->string('quality', 16)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'language')) {
                    $table->string('language', 60)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'commentator')) {
                    $table->string('commentator', 120)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'server_label')) {
                    $table->string('server_label', 80)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'is_recommended')) {
                    $table->boolean('is_recommended')->default(false);
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'health_status')) {
                    $table->string('health_status', 20)->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'last_checked_at')) {
                    $table->timestamp('last_checked_at')->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable();
                }

                if (! Schema::hasColumn('world_cup_match_iptv_item', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration only ensures missing live stream columns exist.
    }
};

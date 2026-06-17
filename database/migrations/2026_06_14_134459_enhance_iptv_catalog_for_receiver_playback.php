<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table): void {
            $table->string('provider_status')->default('unknown')->after('status')->index();
            $table->text('notes')->nullable()->after('last_error');
        });

        Schema::table('iptv_items', function (Blueprint $table): void {
            $table->string('normalized_name')->nullable()->after('name')->index();
            $table->string('tvg_name')->nullable()->after('tvg_id');
            $table->string('stream_type')->default('auto')->after('extension')->index();
            $table->string('quality_label', 16)->default('Auto')->after('stream_type')->index();
            $table->string('language', 32)->nullable()->after('quality_label')->index();
            $table->string('country', 8)->nullable()->after('language')->index();
            $table->boolean('is_featured')->default(false)->after('is_public')->index();
            $table->string('health_status')->default('unknown')->after('is_featured')->index();
            $table->timestamp('last_checked_at')->nullable()->after('health_status')->index();
        });

        Schema::table('watch_histories', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('session_id', 120)->nullable()->after('user_id')->index();
            $table->index(['session_id', 'watched_at']);
        });
    }

    public function down(): void
    {
        Schema::table('watch_histories', function (Blueprint $table): void {
            $table->dropIndex(['session_id', 'watched_at']);
            $table->dropColumn('session_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('iptv_items', function (Blueprint $table): void {
            $table->dropColumn([
                'normalized_name',
                'tvg_name',
                'stream_type',
                'quality_label',
                'language',
                'country',
                'is_featured',
                'health_status',
                'last_checked_at',
            ]);
        });

        Schema::table('playlists', function (Blueprint $table): void {
            $table->dropColumn(['provider_status', 'notes']);
        });
    }
};

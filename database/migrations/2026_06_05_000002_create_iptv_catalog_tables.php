<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table): void {
            $table->string('server_url')->nullable()->after('m3u_url');
            $table->string('username')->nullable()->after('server_url');
            $table->text('password')->nullable()->after('username');
            $table->string('output')->default('mpegts')->after('password');
            $table->unsignedInteger('imported_channels_count')->default(0)->after('status');
            $table->unsignedInteger('imported_movies_count')->default(0)->after('imported_channels_count');
            $table->unsignedInteger('imported_series_count')->default(0)->after('imported_movies_count');
            $table->timestamp('last_imported_at')->nullable()->after('imported_series_count');
            $table->text('last_error')->nullable()->after('last_imported_at');
        });

        Schema::create('iptv_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('external_id')->nullable()->index();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['playlist_id', 'type']);
            $table->unique(['playlist_id', 'type', 'external_id']);
        });

        Schema::create('iptv_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('iptv_categories')->nullOnDelete();
            $table->string('type')->index();
            $table->string('external_id')->nullable()->index();
            $table->string('name')->index();
            $table->text('stream_url')->nullable();
            $table->text('logo')->nullable();
            $table->string('tvg_id')->nullable();
            $table->string('group_title')->nullable();
            $table->string('extension')->nullable();
            $table->string('rating')->nullable();
            $table->text('description')->nullable();
            $table->string('year')->nullable();
            $table->boolean('is_adult')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['playlist_id', 'type']);
            $table->index(['category_id', 'type']);
            $table->unique(['playlist_id', 'type', 'external_id']);
        });

        Schema::table('favorites', function (Blueprint $table): void {
            $table->foreignId('channel_id')->nullable()->change();
            $table->foreignId('iptv_item_id')->nullable()->after('channel_id')->constrained('iptv_items')->cascadeOnDelete();
            $table->unique(['user_id', 'iptv_item_id']);
        });

        Schema::table('watch_histories', function (Blueprint $table): void {
            $table->foreignId('channel_id')->nullable()->change();
            $table->foreignId('iptv_item_id')->nullable()->after('channel_id')->constrained('iptv_items')->cascadeOnDelete();
            $table->unsignedInteger('progress_seconds')->default(0)->after('duration');
            $table->unique(['user_id', 'iptv_item_id']);
        });

        Schema::create('parental_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pin_hash');
            $table->json('locked_category_keywords')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parental_locks');

        Schema::table('watch_histories', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'iptv_item_id']);
            $table->dropConstrainedForeignId('iptv_item_id');
            $table->dropColumn('progress_seconds');
        });

        Schema::table('favorites', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'iptv_item_id']);
            $table->dropConstrainedForeignId('iptv_item_id');
        });

        Schema::dropIfExists('iptv_items');
        Schema::dropIfExists('iptv_categories');

        Schema::table('playlists', function (Blueprint $table): void {
            $table->dropColumn([
                'server_url',
                'username',
                'password',
                'output',
                'imported_channels_count',
                'imported_movies_count',
                'imported_series_count',
                'last_imported_at',
                'last_error',
            ]);
        });
    }
};

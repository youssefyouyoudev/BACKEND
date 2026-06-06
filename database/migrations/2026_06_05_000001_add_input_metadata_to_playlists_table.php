<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table): void {
            $table->string('input_type')->default('remote_url')->after('source_type')->index();
            $table->text('m3u_url')->nullable()->after('source_url');
            $table->string('active_code', 64)->nullable()->after('file_path');
        });

        DB::table('playlists')
            ->where('source_type', 'file')
            ->update(['input_type' => 'upload_file']);

        DB::table('playlists')
            ->where('source_type', 'url')
            ->update(['input_type' => 'remote_url']);

        DB::table('playlists')
            ->whereNull('m3u_url')
            ->whereNotNull('source_url')
            ->update(['m3u_url' => DB::raw('source_url')]);

        Schema::table('playlists', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table): void {
            $table->string('status')->default('ready')->change();
            $table->dropColumn(['input_type', 'm3u_url', 'active_code']);
        });
    }
};

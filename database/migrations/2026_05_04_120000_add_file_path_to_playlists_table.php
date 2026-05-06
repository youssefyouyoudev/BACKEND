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
            $table->string('file_path')->nullable()->after('source_url');
        });

        DB::table('playlists')
            ->whereNull('file_path')
            ->whereNotNull('stored_path')
            ->update([
                'file_path' => DB::raw('stored_path'),
            ]);
    }

    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table): void {
            $table->dropColumn('file_path');
        });
    }
};

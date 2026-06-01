<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_streams', function (Blueprint $table): void {
            $table->unsignedInteger('response_code')->nullable()->after('latency_ms');
            $table->timestamp('last_success_at')->nullable()->after('last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('channel_streams', function (Blueprint $table): void {
            $table->dropColumn(['response_code', 'last_success_at']);
        });
    }
};

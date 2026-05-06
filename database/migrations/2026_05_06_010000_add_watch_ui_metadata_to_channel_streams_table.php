<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_streams', function (Blueprint $table) {
            $table->string('source_code', 12)->nullable()->after('label')->index();
            $table->string('server_name')->nullable()->after('source_code');
            $table->string('server_region')->nullable()->after('server_name');
            $table->string('quality', 24)->default('1080p')->after('server_region');
            $table->string('health_status', 24)->default('unchecked')->after('quality')->index();
            $table->unsignedInteger('latency_ms')->nullable()->after('health_status');
            $table->unsignedSmallInteger('failure_count')->default(0)->after('latency_ms');
            $table->unsignedSmallInteger('success_count')->default(0)->after('failure_count');
            $table->text('last_error')->nullable()->after('success_count');
            $table->timestamp('last_checked_at')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('channel_streams', function (Blueprint $table) {
            $table->dropColumn([
                'source_code',
                'server_name',
                'server_region',
                'quality',
                'health_status',
                'latency_ms',
                'failure_count',
                'success_count',
                'last_error',
                'last_checked_at',
            ]);
        });
    }
};

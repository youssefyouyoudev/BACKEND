<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_server_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_stream_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('unchecked')->index();
            $table->string('probe_type', 24)->default('playlist');
            $table->unsignedInteger('http_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedBigInteger('bytes_received')->nullable();
            $table->text('message')->nullable();
            $table->json('diagnostics')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_server_statuses');
    }
};

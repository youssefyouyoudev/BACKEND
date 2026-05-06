<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failover_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_channel_stream_id')->nullable()->constrained('channel_streams')->nullOnDelete();
            $table->foreignId('to_channel_stream_id')->nullable()->constrained('channel_streams')->nullOnDelete();
            $table->string('event_type', 40)->index();
            $table->string('severity', 16)->default('info')->index();
            $table->string('rule_code', 40)->nullable()->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failover_logs');
    }
};

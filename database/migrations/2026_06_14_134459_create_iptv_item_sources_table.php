<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iptv_item_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('iptv_item_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Primary');
            $table->text('url');
            $table->string('type')->default('auto')->index();
            $table->string('quality_label', 16)->default('Auto');
            $table->unsignedSmallInteger('priority')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->string('health_status')->default('unknown')->index();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_checked_at')->nullable()->index();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();

            $table->index(['iptv_item_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iptv_item_sources');
    }
};

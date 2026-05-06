<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->string('tvg_id')->nullable()->index();
            $table->string('name');
            $table->text('logo')->nullable();
            $table->string('group_title')->nullable()->index();
            $table->text('stream_url');
            $table->string('stream_type')->nullable();
            $table->string('stream_hash', 64)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('featured_rank')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['playlist_id', 'stream_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};

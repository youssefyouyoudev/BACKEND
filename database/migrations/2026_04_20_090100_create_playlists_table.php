<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('source_type');
            $table->text('source_url')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->string('status')->default('ready')->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->foreignId('approved_by_admin')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('import_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};

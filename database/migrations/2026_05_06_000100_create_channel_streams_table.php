<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->text('stream_url');
            $table->string('stream_hash', 64)->index();
            $table->string('stream_type', 20)->default('hls');
            // Priority order: 1 = primary (lowest number = first tried)
            $table->unsignedSmallInteger('priority')->default(1)->index();
            $table->boolean('is_active')->default(true)->index();
            // Optional label to help admins identify the source server
            $table->string('label')->nullable()->comment('e.g. Server 1, Backup EU, CDN-2');
            $table->timestamps();

            $table->unique('stream_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_streams');
    }
};

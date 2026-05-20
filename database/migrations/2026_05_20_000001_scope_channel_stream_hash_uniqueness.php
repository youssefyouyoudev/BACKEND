<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_streams', function (Blueprint $table) {
            $table->dropUnique(['stream_hash']);
            $table->unique(['channel_id', 'stream_hash'], 'channel_streams_channel_stream_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('channel_streams', function (Blueprint $table) {
            $table->dropUnique('channel_streams_channel_stream_hash_unique');
            $table->unique('stream_hash');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evolves the channels table for the "one channel, multiple stream sources"
 * de-duplication model.
 *
 * MySQL quirk: the composite unique index `channels_playlist_id_stream_hash_unique`
 * cannot be dropped while the `playlist_id` foreign key exists, because MySQL
 * uses that index to enforce the FK constraint (it is the leftmost key).
 *
 * Fix order:
 *   1. Add channel_identity_hash column + standalone index on playlist_id
 *   2. Drop the FK (releases the dependency on the composite index)
 *   3. Drop the composite unique index
 *   4. Re-add the FK (MySQL will use the standalone playlist_id index)
 *   5. Add the new identity unique constraint
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // Step 1a – Add the new identity hash column
            $table->string('channel_identity_hash', 64)
                ->nullable()
                ->after('stream_hash')
                ->comment('sha1(playlist_id + normalized_name + group_title)');

            // Step 1b – Add a standalone index on playlist_id so the FK can
            // survive after we drop the composite index
            $table->index('playlist_id', 'channels_playlist_id_plain_index');

            // Step 2 – Drop the FK (frees the composite index from FK duty)
            $table->dropForeign(['playlist_id']);

            // Step 3 – Now safe to drop the composite unique index
            $table->dropUnique(['playlist_id', 'stream_hash']);

            // Step 4 – Re-add the FK (will use the standalone index above)
            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete();

            // Step 5 – New unique constraint based on channel identity
            $table->unique(['playlist_id', 'channel_identity_hash'], 'channels_playlist_identity_unique');

            // Step 6 – Index the identity hash on its own for fast lookups
            $table->index('channel_identity_hash', 'channels_identity_hash_index');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex('channels_identity_hash_index');
            $table->dropUnique('channels_playlist_identity_unique');
            $table->dropForeign(['playlist_id']);
            $table->unique(['playlist_id', 'stream_hash']);
            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete();
            $table->dropIndex('channels_playlist_id_plain_index');
            $table->dropColumn('channel_identity_hash');
        });
    }
};


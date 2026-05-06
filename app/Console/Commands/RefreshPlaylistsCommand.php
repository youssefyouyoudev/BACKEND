<?php

namespace App\Console\Commands;

use App\Jobs\ParsePlaylistJob;
use App\Models\Playlist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RefreshPlaylistsCommand extends Command
{
    protected $signature = 'playlists:refresh {playlist_id? : Optional playlist id to refresh immediately}';

    protected $description = 'Queue playlist refresh jobs for one or all saved playlists.';

    public function handle(): int
    {
        $playlistId = $this->argument('playlist_id');

        $playlists = Playlist::query()
            ->when($playlistId, fn ($query) => $query->whereKey($playlistId))
            ->get();

        if ($playlists->isEmpty()) {
            $this->error('No playlists matched the provided criteria.');

            return self::FAILURE;
        }

        $shouldRunSynchronously = $this->shouldRunSynchronously();

        foreach ($playlists as $playlist) {
            $playlist->update(['status' => 'queued']);

            if ($shouldRunSynchronously) {
                ParsePlaylistJob::dispatchSync($playlist->id);
                continue;
            }

            ParsePlaylistJob::dispatch($playlist->id);
        }

        $this->info($shouldRunSynchronously
            ? sprintf('Processed %d playlist(s) synchronously.', $playlists->count())
            : sprintf('Queued %d playlist refresh job(s).', $playlists->count()));

        return self::SUCCESS;
    }

    private function shouldRunSynchronously(): bool
    {
        $queueConnection = (string) config('queue.default', 'sync');

        if ($queueConnection === 'sync') {
            return true;
        }

        if ($queueConnection === 'database') {
            $table = (string) config('queue.connections.database.table', 'jobs');

            return ! Schema::hasTable($table);
        }

        return false;
    }
}

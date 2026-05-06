<?php

namespace App\Console\Commands;

use App\Jobs\ParsePlaylistJob;
use App\Models\Playlist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Synchronize all (or a specific) active playlists on a schedule.
 *
 * Designed to be called by Laravel's task scheduler every 24 hours via:
 *
 *   Schedule::command('playlists:sync')->daily()->withoutOverlapping()->onOneServer();
 *
 * The command is also useful for manual one-off syncs from the CLI:
 *
 *   php artisan playlists:sync
 *   php artisan playlists:sync 42          # sync playlist ID 42 only
 *   php artisan playlists:sync --force     # ignore completed/failed status
 */
class SyncPlaylistsCommand extends Command
{
    protected $signature = 'playlists:sync
                            {playlist_id? : Sync a specific playlist by ID (optional)}
                            {--force       : Also sync playlists that are already completed or failed}';

    protected $description = 'Scheduled M3U sync: fetch, parse, and de-duplicate playlists every 24 hours.';

    public function handle(): int
    {
        $playlistId = $this->argument('playlist_id');
        $force      = (bool) $this->option('force');

        $query = Playlist::query()
            ->when($playlistId, fn ($q) => $q->whereKey($playlistId))
            ->unless($force, fn ($q) => $q->whereNotIn('status', ['processing', 'queued']));

        $playlists = $query->get();

        if ($playlists->isEmpty()) {
            $this->warn('No playlists found matching the criteria.');

            return self::SUCCESS;
        }

        $shouldRunSynchronously = $this->shouldRunSynchronously();

        $bar = $this->output->createProgressBar($playlists->count());
        $bar->start();

        foreach ($playlists as $playlist) {
            $playlist->update(['status' => 'queued']);

            if ($shouldRunSynchronously) {
                ParsePlaylistJob::dispatchSync($playlist->id);
            } else {
                ParsePlaylistJob::dispatch($playlist->id);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info(
            $shouldRunSynchronously
                ? sprintf('✓ Synced %d playlist(s) synchronously.', $playlists->count())
                : sprintf('✓ Queued %d playlist sync job(s).', $playlists->count())
        );

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

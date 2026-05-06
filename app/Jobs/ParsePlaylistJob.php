<?php

namespace App\Jobs;

use App\Models\Playlist;
use App\Services\PlaylistImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ParsePlaylistJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** Allow up to 5 minutes for large M3U files. */
    public int $timeout = 300;

    /** Retry up to 3 times with exponential backoff. */
    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $playlistId,
    ) {
    }

    public function handle(PlaylistImportService $playlistImportService): void
    {
        $playlist = Playlist::query()->findOrFail($this->playlistId);

        $playlistImportService->process($playlist);
    }

    public function failed(Throwable $exception): void
    {
        $playlist = Playlist::query()->find($this->playlistId);

        if ($playlist === null) {
            return;
        }

        $playlist->forceFill([
            'status' => 'failed',
            'import_summary' => [
                'error' => $exception->getMessage(),
                'groups' => [],
                'total_channels' => 0,
            ],
        ])->save();
    }
}

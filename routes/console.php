<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled M3U Playlist Synchronization
|--------------------------------------------------------------------------
|
| Runs every 24 hours to re-fetch, parse, and de-duplicate all playlists.
|
| withoutOverlapping() — prevents a second sync starting if a previous run
|   is still in progress (important for large playlists that may take time).
|
| onOneServer() — ensures only one server fires this job in a multi-server
|   deployment (requires a shared cache like Redis or Memcached).
|
| runInBackground() — does not block the scheduler worker process.
|
| To register the Windows Task Scheduler (development):
|   php artisan schedule:work
|
| Production crontab entry:
|   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
|
*/
Schedule::command('playlists:sync')
    ->daily()                     // Run once every 24 hours at midnight
    ->withoutOverlapping(180)     // Prevent overlap for up to 3 hours
    ->onOneServer()               // One-server lock (requires shared cache)
    ->runInBackground()           // Non-blocking scheduler worker
    ->appendOutputTo(storage_path('logs/playlist-sync.log'));

Schedule::command('m3u:sync-failover --probe')
    ->dailyAt('03:30')
    ->withoutOverlapping(180)
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/m3u-failover-sync.log'));

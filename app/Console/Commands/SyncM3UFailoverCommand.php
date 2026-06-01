<?php

namespace App\Console\Commands;

use App\Models\ChannelStream;
use App\Models\FailoverLog;
use App\Models\Playlist;
use App\Models\StreamServerStatus;
use App\Services\PlaylistImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncM3UFailoverCommand extends Command
{
    protected $signature = 'm3u:sync-failover
                            {playlist_id? : Optional playlist id to parse and probe}
                            {--probe : Probe stream URLs after parsing}';

    protected $description = 'Parse M3U playlists, refresh source metadata, probe server health, and write failover events.';

    public function handle(PlaylistImportService $playlistImportService): int
    {
        $playlistId = $this->argument('playlist_id');

        $playlists = Playlist::query()
            ->when($playlistId, fn ($query) => $query->whereKey($playlistId))
            ->get();

        if ($playlists->isEmpty()) {
            $this->warn('No playlists matched the provided criteria.');

            return self::SUCCESS;
        }

        foreach ($playlists as $playlist) {
            $this->line("Parsing M3U playlist data for {$playlist->name}...");

            try {
                $playlistImportService->process($playlist);
            } catch (Throwable $exception) {
                $this->error("Playlist {$playlist->id} failed: {$exception->getMessage()}");
                continue;
            }

            if ($this->option('probe') && Schema::hasTable('stream_server_statuses')) {
                $this->probePlaylistStreams($playlist->fresh());
            }
        }

        $this->info('M3U failover sync complete.');

        return self::SUCCESS;
    }

    private function probePlaylistStreams(Playlist $playlist): void
    {
        $streams = ChannelStream::query()
            ->whereHas('channel', fn ($query) => $query->where('playlist_id', $playlist->id))
            ->with('channel')
            ->orderBy('channel_id')
            ->orderBy('priority')
            ->get();

        $this->withProgressBar($streams, function (ChannelStream $stream): void {
            $started = microtime(true);
            $status = 'active';
            $httpStatus = null;
            $bytes = null;
            $message = 'Source reachable.';

            try {
                $response = Http::connectTimeout(3)
                    ->timeout(5)
                    ->retry(1, 200)
                    ->withHeaders(['Range' => 'bytes=0-2048'])
                    ->get($stream->stream_url);

                $httpStatus = $response->status();
                $bytes = strlen((string) $response->body());

                if (! $response->successful()) {
                    $status = in_array($httpStatus, [408, 425, 429, 500, 502, 503, 504], true) ? 'timeout' : 'failed';
                    $message = "Probe returned HTTP {$httpStatus}.";
                }
            } catch (Throwable $exception) {
                $status = str_contains(strtolower($exception->getMessage()), 'timed out') ? 'timeout' : 'failed';
                $message = $exception->getMessage();
            }

            $latency = (int) round((microtime(true) - $started) * 1000);

            StreamServerStatus::query()->create([
                'channel_stream_id' => $stream->id,
                'status' => $status,
                'probe_type' => 'playlist',
                'http_status' => $httpStatus,
                'latency_ms' => $latency,
                'bytes_received' => $bytes,
                'message' => $message,
                'diagnostics' => ['url_hash' => $stream->stream_hash],
                'checked_at' => now(),
            ]);

            $stream->forceFill([
                'health_status' => $status,
                'latency_ms' => $latency,
                'response_code' => $httpStatus,
                'last_error' => $status === 'active' ? null : $message,
                'last_checked_at' => now(),
                'last_success_at' => $status === 'active' ? now() : $stream->last_success_at,
                'success_count' => $status === 'active' ? $stream->success_count + 1 : $stream->success_count,
                'failure_count' => $status === 'active' ? $stream->failure_count : $stream->failure_count + 1,
            ])->save();

            if ($status !== 'active') {
                $backup = ChannelStream::query()
                    ->where('channel_id', $stream->channel_id)
                    ->where('id', '!=', $stream->id)
                    ->whereIn('health_status', ['active', 'standby', 'unchecked'])
                    ->orderBy('priority')
                    ->first();

                FailoverLog::query()->create([
                    'channel_id' => $stream->channel_id,
                    'from_channel_stream_id' => $stream->id,
                    'to_channel_stream_id' => $backup?->id,
                    'event_type' => 'server_failover',
                    'severity' => 'critical',
                    'rule_code' => 'RULE-24',
                    'message' => sprintf(
                        "Connection failed on '%s'. Auto-failover prepared%s.",
                        $stream->source_code ?: $stream->label ?: 'Source',
                        $backup ? " to '{$backup->source_code}'" : ''
                    ),
                    'context' => [
                        'from_region' => $stream->server_region,
                        'to_region' => $backup?->server_region,
                        'latency_ms' => $latency,
                        'probe_message' => $message,
                    ],
                    'occurred_at' => now(),
                ]);
            }
        });

        $this->newLine();
    }
}

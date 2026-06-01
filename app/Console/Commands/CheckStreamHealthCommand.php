<?php

namespace App\Console\Commands;

use App\Models\ChannelStream;
use App\Models\StreamServerStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckStreamHealthCommand extends Command
{
    protected $signature = 'streams:check-health
        {--limit=200 : Maximum streams to check this run}
        {--popular : Prioritize featured channels and recently successful sources}';

    protected $description = 'Check IPTV stream health with short non-blocking probes and store status metadata.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $streams = ChannelStream::query()
            ->where('is_active', true)
            ->with('channel')
            ->when($this->option('popular'), function ($query): void {
                $query->where(function ($popularQuery): void {
                    $popularQuery->whereHas('channel', fn ($channelQuery) => $channelQuery->where('is_featured', true))
                        ->orWhereNotNull('last_success_at');
                });
            })
            ->orderByRaw('last_checked_at IS NULL DESC')
            ->orderBy('last_checked_at')
            ->limit($limit)
            ->get();

        if ($streams->isEmpty()) {
            $this->info('No active channel streams to check.');

            return self::SUCCESS;
        }

        $this->withProgressBar($streams, fn (ChannelStream $stream) => $this->checkStream($stream));
        $this->newLine();
        $this->info("Checked {$streams->count()} stream sources.");

        return self::SUCCESS;
    }

    private function checkStream(ChannelStream $stream): void
    {
        $started = microtime(true);
        $status = 'offline';
        $responseCode = null;
        $bytes = null;
        $message = null;

        try {
            $response = Http::connectTimeout(3)
                ->timeout(5)
                ->retry(1, 200)
                ->withHeaders([
                    'Range' => 'bytes=0-2048',
                    'Accept' => '*/*',
                    'User-Agent' => 'RifiMediaHealthCheck/1.0',
                ])
                ->get($stream->stream_url);

            $responseCode = $response->status();
            $bytes = strlen((string) $response->body());
            $status = $response->successful() || in_array($responseCode, [206, 301, 302, 403, 405], true)
                ? 'online'
                : 'offline';
            $message = $status === 'online' ? 'Source reachable.' : "Probe returned HTTP {$responseCode}.";
        } catch (Throwable $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'timed out') ? 'unknown' : 'offline';
            $message = $exception->getMessage();
        }

        $latency = (int) round((microtime(true) - $started) * 1000);
        $checkedAt = now();

        $stream->forceFill([
            'health_status' => $status,
            'latency_ms' => $latency,
            'response_code' => $responseCode,
            'last_error' => $status === 'online' ? null : $message,
            'last_checked_at' => $checkedAt,
            'last_success_at' => $status === 'online' ? $checkedAt : $stream->last_success_at,
            'success_count' => $status === 'online' ? $stream->success_count + 1 : $stream->success_count,
            'failure_count' => $status === 'online' ? $stream->failure_count : $stream->failure_count + 1,
        ])->save();

        StreamServerStatus::query()->create([
            'channel_stream_id' => $stream->id,
            'status' => $status,
            'probe_type' => 'health',
            'http_status' => $responseCode,
            'latency_ms' => $latency,
            'bytes_received' => $bytes,
            'message' => $message,
            'diagnostics' => ['stream_hash' => $stream->stream_hash],
            'checked_at' => $checkedAt,
        ]);

        Log::info('stream.health_checked', [
            'channel_id' => $stream->channel_id,
            'channel_stream_id' => $stream->id,
            'status' => $status,
            'response_code' => $responseCode,
            'latency_ms' => $latency,
        ]);
    }
}

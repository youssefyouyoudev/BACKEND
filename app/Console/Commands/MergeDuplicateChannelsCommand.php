<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelStream;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeDuplicateChannelsCommand extends Command
{
    protected $signature = 'channels:merge-duplicates {--dry-run : Report what would change without writing}';

    protected $description = 'Merge exact duplicate channel names into one active channel with multiple stream servers.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $mergedChannels = 0;
        $movedStreams = 0;

        $this->backfillNormalizedNames($dryRun);

        Channel::query()
            ->with(['streams' => fn ($query) => $query->orderBy('priority')])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Channel $channel): string => $channel->normalized_name ?: $this->normalizeChannelName($channel->name))
            ->filter(fn (Collection $channels, string $key): bool => $key !== '')
            ->filter(fn (Collection $channels): bool => $channels->count() > 1)
            ->each(function (Collection $duplicates) use ($dryRun, &$mergedChannels, &$movedStreams): void {
                /** @var Channel $keeper */
                $ordered = $duplicates
                    ->sort(function (Channel $left, Channel $right): int {
                        return [
                            $left->is_active ? 0 : 1,
                            $left->created_at?->getTimestamp() ?? 0,
                            $left->id,
                        ] <=> [
                            $right->is_active ? 0 : 1,
                            $right->created_at?->getTimestamp() ?? 0,
                            $right->id,
                        ];
                    })
                    ->values();

                $keeper = $ordered->first();
                $toMerge = $ordered->slice(1);

                $this->line(sprintf(
                    '%s "%s" keeps channel #%d and merges %d duplicate(s).',
                    $dryRun ? '[dry-run]' : '[merge]',
                    $keeper->name,
                    $keeper->id,
                    $toMerge->count()
                ));

                if ($dryRun) {
                    $mergedChannels += $toMerge->count();
                    $movedStreams += $toMerge->sum(fn (Channel $channel): int => $channel->streams->count() + ($channel->stream_url ? 1 : 0));

                    return;
                }

                DB::transaction(function () use ($keeper, $toMerge, &$mergedChannels, &$movedStreams): void {
                    $keeper->loadMissing('streams');

                    foreach ($toMerge as $duplicate) {
                        $movedStreams += $this->moveStreams($keeper, $duplicate);

                        $keeper->forceFill([
                            'normalized_name' => $keeper->normalized_name ?: $this->normalizeChannelName($keeper->name),
                            'logo' => $keeper->logo ?: $duplicate->logo,
                            'group_title' => $keeper->group_title ?: $duplicate->group_title,
                            'tvg_id' => $keeper->tvg_id ?: $duplicate->tvg_id,
                        ])->save();

                        $duplicate->forceFill(['is_active' => false])->save();
                        $mergedChannels++;
                    }
                });
            });

        $this->info("Merged {$mergedChannels} duplicate channel rows and moved {$movedStreams} stream source(s).");

        return self::SUCCESS;
    }

    private function backfillNormalizedNames(bool $dryRun): void
    {
        $channels = Channel::query()
            ->where(function ($query): void {
                $query->whereNull('normalized_name')->orWhere('normalized_name', '');
            })
            ->get(['id', 'name', 'normalized_name']);

        if ($channels->isEmpty()) {
            return;
        }

        $this->line(sprintf(
            '%s Backfilling normalized_name for %d channel row(s).',
            $dryRun ? '[dry-run]' : '[backfill]',
            $channels->count()
        ));

        if ($dryRun) {
            return;
        }

        $channels->each(function (Channel $channel): void {
            $channel->forceFill([
                'normalized_name' => $this->normalizeChannelName($channel->name),
            ])->save();
        });
    }

    private function moveStreams(Channel $keeper, Channel $duplicate): int
    {
        $this->ensurePrimaryStream($keeper);
        $keeper->load('streams');
        $existingHashes = $keeper->streams->pluck('stream_hash')->map(fn ($hash) => strtolower((string) $hash))->all();
        $existingHashes = array_fill_keys($existingHashes, true);
        $nextPriority = ((int) $keeper->streams->max('priority')) + 1;
        $moved = 0;

        $sources = collect();

        if ($duplicate->stream_url) {
            $sources->push([
                'stream_url' => $duplicate->stream_url,
                'stream_hash' => $duplicate->stream_hash ?: sha1($this->normalizeUrlForHash($duplicate->stream_url)),
                'stream_type' => $duplicate->stream_type ?: 'stream',
            ]);
        }

        foreach ($duplicate->streams as $stream) {
            $sources->push([
                'stream_url' => $stream->stream_url,
                'stream_hash' => $stream->stream_hash ?: sha1($this->normalizeUrlForHash($stream->stream_url)),
                'stream_type' => $stream->stream_type ?: 'stream',
                'quality' => $stream->quality,
                'health_status' => $stream->health_status,
            ]);
        }

        foreach ($sources as $source) {
            $hash = strtolower((string) $source['stream_hash']);

            if (isset($existingHashes[$hash])) {
                continue;
            }

            ChannelStream::query()->create([
                'channel_id' => $keeper->id,
                'stream_url' => $source['stream_url'],
                'stream_hash' => $hash,
                'stream_type' => $source['stream_type'] ?? 'stream',
                'priority' => $nextPriority,
                'is_active' => true,
                'label' => 'Server '.$nextPriority,
                'source_code' => 'S'.$nextPriority,
                'server_name' => 'Server '.$nextPriority,
                'quality' => $source['quality'] ?? '1080p',
                'health_status' => $source['health_status'] ?? 'unknown',
            ]);

            $existingHashes[$hash] = true;
            $nextPriority++;
            $moved++;
        }

        return $moved;
    }

    private function ensurePrimaryStream(Channel $channel): void
    {
        $channel->loadMissing('streams');

        if (! $channel->stream_url || $channel->streams->isNotEmpty()) {
            return;
        }

        ChannelStream::query()->create([
            'channel_id' => $channel->id,
            'stream_url' => $channel->stream_url,
            'stream_hash' => $channel->stream_hash ?: sha1($this->normalizeUrlForHash($channel->stream_url)),
            'stream_type' => $channel->stream_type ?: 'stream',
            'priority' => 1,
            'is_active' => true,
            'label' => 'Server 1',
            'source_code' => 'S1',
            'server_name' => 'Server 1',
            'quality' => '1080p',
            'health_status' => 'unknown',
        ]);
    }

    private function normalizeChannelName(?string $name): string
    {
        return Channel::normalizeName($name);
    }

    private function normalizeUrlForHash(string $url): string
    {
        return strtolower(trim($url));
    }
}

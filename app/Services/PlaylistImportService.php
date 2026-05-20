<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\ChannelStream;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PlaylistImportService
{
    /** Number of channel rows to insert per batch. */
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly M3UParserService $parser,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function importFromUrl(User $user, array $attributes): Playlist
    {
        $playlist = Playlist::query()->create([
            'user_id'     => $user->id,
            'name'        => $attributes['name'] ?: 'Imported Playlist',
            'source_type' => Playlist::SOURCE_TYPE_URL,
            'source_url'  => $attributes['source_url'],
            'status'      => 'pending',
            'is_public'   => (bool) ($attributes['is_public'] ?? false),
        ]);

        return $this->process($playlist);
    }

    public function importFromUpload(User $user, array $attributes, UploadedFile $file): Playlist
    {
        $storedPath = $this->storePlaylistFile($file);

        $playlist = Playlist::query()->create([
            'user_id'           => $user->id,
            'name'              => $attributes['name'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'source_type'       => Playlist::SOURCE_TYPE_FILE,
            'file_path'         => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $storedPath,
            'status'            => 'pending',
            'is_public'         => (bool) ($attributes['is_public'] ?? false),
        ]);

        return $this->process($playlist);
    }

    public function refresh(Playlist $playlist): Playlist
    {
        return $this->process($playlist);
    }

    public function process(Playlist $playlist): Playlist
    {
        $playlist->forceFill(['status' => 'processing'])->save();

        try {
            $parsed = $this->parser->parsePlaylist($playlist);

            $now = now()->toDateTimeString();

            // ── Step 1: Group entries by channel identity (name + group_title) ──
            // Entries with the same normalized channel name are the same channel on multiple servers.
            $groupedByIdentity = $this->groupEntriesByIdentity($parsed['entries'], $playlist->id);

            $totalChannels  = 0;
            $totalStreams    = 0;

            DB::transaction(function () use ($playlist, $parsed, $groupedByIdentity, $now, &$totalChannels, &$totalStreams): void {
                // Wipe existing channels + cascade to channel_streams
                $playlist->channels()->delete();

                $sortOrder = 1;

                // Chunk the identity groups for memory-efficient bulk insert
                foreach (array_chunk($groupedByIdentity, self::CHUNK_SIZE, true) as $chunk) {
                    $channelRows = [];

                    foreach ($chunk as $identityHash => $entries) {
                        // Use the first entry as the canonical channel metadata
                        $primary = $entries[0];

                        $channelRows[] = [
                            'playlist_id'           => $playlist->id,
                            'tvg_id'                => $primary['tvg_id'],
                            'name'                  => $primary['name'],
                            'logo'                  => $primary['logo'],
                            'group_title'           => $primary['group_title'],
                            // Keep stream_url pointing to the primary URL for backward compatibility
                            'stream_url'            => $primary['stream_url'],
                            'stream_type'           => $primary['stream_type'],
                            'stream_hash'           => $primary['stream_hash'],
                            'channel_identity_hash' => $identityHash,
                            'is_active'             => true,
                            'sort_order'            => $sortOrder++,
                            'metadata'              => json_encode($primary['metadata']),
                            'created_at'            => $now,
                            'updated_at'            => $now,
                        ];

                        $totalChannels++;
                    }

                    Channel::query()->insert($channelRows);

                    // Reload inserted channels by identity hash to get their IDs
                    $insertedChannels = Channel::query()
                        ->where('playlist_id', $playlist->id)
                        ->whereIn('channel_identity_hash', array_keys($chunk))
                        ->pluck('id', 'channel_identity_hash');

                    // Build stream rows for all entries in this chunk
                    $streamRows = [];

                    foreach ($chunk as $identityHash => $entries) {
                        $channelId = $insertedChannels[$identityHash] ?? null;

                        if ($channelId === null) {
                            continue;
                        }

                        foreach (array_values($entries) as $priority => $entry) {
                            $serverNumber = $priority + 1;
                            $serverLabel = 'Server '.$serverNumber;

                            $streamRows[] = [
                                'channel_id'  => $channelId,
                                'stream_url'  => $entry['stream_url'],
                                'stream_hash' => $entry['stream_hash'],
                                'stream_type' => $entry['stream_type'],
                                'priority'    => $serverNumber,
                                'is_active'   => true,
                                'label'       => $serverLabel,
                                'source_code' => 'S'.$serverNumber,
                                'server_name' => $serverLabel,
                                'server_region' => null,
                                'quality' => '1080p',
                                'health_status' => 'unknown',
                                'created_at'  => $now,
                                'updated_at'  => $now,
                            ];
                            $totalStreams++;
                        }
                    }

                    if ($streamRows !== []) {
                        // Ignore duplicate URLs for the same channel.
                        ChannelStream::query()->insertOrIgnore($streamRows);
                    }
                }

                $playlist->forceFill([
                    'name'           => $playlist->name ?: ($parsed['title'] ?: 'Imported Playlist'),
                    'status'         => 'completed',
                    'last_synced_at' => now(),
                    'import_summary' => [
                        'imported'       => $totalChannels,
                        'stream_sources' => $totalStreams,
                        'updated'        => 0,
                        'removed'        => 0,
                        'groups'         => $parsed['groups'],
                        'total_channels' => $totalChannels,
                    ],
                ])->save();
            });

            $playlist->refresh();

            $this->activityLogService->log(
                $playlist->user,
                'playlist.imported',
                $playlist,
                [
                    'status'         => $playlist->status,
                    'channels'       => $totalChannels,
                    'stream_sources' => $totalStreams,
                    'source_type'    => $playlist->source_type,
                ]
            );

            return $playlist->load(['channels.streams'])->loadCount('channels');
        } catch (Throwable $exception) {
            $this->markImportFailed($playlist, $playlist->user, $exception, 'playlist.import.failed');

            throw $exception;
        }
    }

    public function storePlaylistFile(UploadedFile $file): string
    {
        $filename = Str::uuid()->toString().'-'.preg_replace(
            '/[^a-zA-Z0-9.\-_]/',
            '-',
            $file->getClientOriginalName()
        );

        return $file->storeAs('', $filename, 'playlists');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Group parsed M3U entries by a deterministic channel identity hash.
     *
     * The identity is sha1( playlist_id | normalized_name ).
     * Entries that share an identity are the same channel on multiple servers
     * and will be stored as separate ChannelStream rows under one Channel row.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupEntriesByIdentity(array $entries, int $playlistId): array
    {
        $grouped = [];
        $seenStreamHashesByIdentity = [];

        foreach ($entries as $entry) {
            $identityHash = $this->buildIdentityHash($playlistId, $entry['name']);
            $streamHash = strtolower((string) $entry['stream_hash']);

            // Skip truly duplicate stream URLs (identical URL on same channel)
            if (isset($seenStreamHashesByIdentity[$identityHash][$streamHash])) {
                continue;
            }

            $seenStreamHashesByIdentity[$identityHash][$streamHash] = true;

            $grouped[$identityHash][] = $entry;
        }

        return $grouped;
    }

    /**
     * Build a deterministic hash that identifies a channel regardless of URL.
     */
    private function buildIdentityHash(int $playlistId, ?string $name): string
    {
        $normalizedName = $this->normalizeChannelName($name);

        return sha1("{$playlistId}|{$normalizedName}");
    }

    private function normalizeChannelName(?string $name): string
    {
        $normalized = (string) preg_replace('/\s+/u', ' ', trim((string) $name));

        return mb_strtolower($normalized);
    }

    private function markImportFailed(Playlist $playlist, User $user, Throwable $exception, string $action): void
    {
        $playlist->forceFill([
            'status'         => 'failed',
            'import_summary' => [
                'error'          => $exception->getMessage(),
                'groups'         => [],
                'total_channels' => 0,
            ],
        ])->save();

        Log::warning('Playlist import failed', [
            'playlist_id' => $playlist->id,
            'user_id'     => $user->id,
            'message'     => $exception->getMessage(),
        ]);

        $this->activityLogService->log($user, $action, $playlist, [
            'message' => $exception->getMessage(),
        ]);
    }
}

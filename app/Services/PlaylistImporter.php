<?php

namespace App\Services;

use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlaylistImporter
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly M3UParser $parser,
        private readonly PlaylistUrlBuilder $urlBuilder,
        private readonly UrlSafetyService $urlSafetyService,
        private readonly XtreamImporter $xtreamImporter,
        private readonly StreamingPolicy $streamingPolicy,
    ) {}

    public function import(Playlist $playlist): Playlist
    {
        if ($playlist->input_type === Playlist::INPUT_TYPE_ACTIVE_CODE && blank($playlist->m3u_url)) {
            $playlist->forceFill(['status' => 'needs_url'])->save();

            return $playlist;
        }

        $playlist->forceFill(['status' => 'importing', 'last_error' => null])->save();

        try {
            if ($playlist->input_type === Playlist::INPUT_TYPE_XTREAM) {
                try {
                    return $this->xtreamImporter->import($playlist);
                } catch (Throwable) {
                    $playlist->m3u_url = $this->urlBuilder->buildFromXtream(
                        (string) $playlist->server_url,
                        (string) $playlist->username,
                        (string) $playlist->password,
                        (string) $playlist->output
                    );
                }
            }

            $content = $this->loadM3uContent($playlist);
            $items = $this->parser->parse($content);

            if ($items === []) {
                throw ValidationException::withMessages([
                    'playlist' => [__('The playlist did not contain any importable IPTV items.')],
                ]);
            }

            return $this->saveParsedItems($playlist, $items);
        } catch (Throwable $exception) {
            $playlist->forceFill([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function saveParsedItems(Playlist $playlist, array $items): Playlist
    {
        $items = array_values(array_filter(
            $items,
            fn (array $item): bool => $this->streamingPolicy->allowsStreamUrl($item['stream_url'] ?? null)
        ));

        if ($items === []) {
            throw ValidationException::withMessages([
                'playlist' => [__('No stream entries matched the approved legal domain allowlist.')],
            ]);
        }

        $now = now();
        $counts = ['live' => 0, 'movie' => 0, 'series' => 0];

        DB::transaction(function () use ($playlist, $items, $now, &$counts): void {
            $publicationChoices = $playlist->iptvItems()
                ->get(['type', 'external_id', 'is_public'])
                ->mapWithKeys(fn (IptvItem $item): array => [
                    $item->type.'|'.$item->external_id => $item->is_public,
                ]);

            $playlist->iptvItems()->delete();
            $playlist->iptvCategories()->delete();

            $categoryIds = [];
            $sortOrder = 0;

            foreach (collect($items)->groupBy(fn (array $item) => ($item['type'] ?? 'live').'|'.($item['group_title'] ?? 'Uncategorized')) as $key => $group) {
                [$type, $name] = explode('|', (string) $key, 2);
                $category = IptvCategory::query()->create([
                    'playlist_id' => $playlist->id,
                    'type' => $type,
                    'external_id' => null,
                    'name' => $name !== '' ? $name : 'Uncategorized',
                    'sort_order' => $sortOrder++,
                ]);

                $categoryIds[$key] = $category->id;
            }

            foreach (array_chunk($items, self::CHUNK_SIZE) as $chunk) {
                $rows = [];

                foreach ($chunk as $item) {
                    $type = $item['type'] ?? 'live';
                    $externalId = $item['external_id'] ?? sha1((string) ($item['stream_url'] ?? $item['name']));
                    $categoryKey = $type.'|'.($item['group_title'] ?? 'Uncategorized');
                    $counts[$type] = ($counts[$type] ?? 0) + 1;

                    $rows[] = [
                        'playlist_id' => $playlist->id,
                        'category_id' => $categoryIds[$categoryKey] ?? null,
                        'type' => $type,
                        'external_id' => $externalId,
                        'name' => $item['name'] ?? 'Untitled',
                        'stream_url' => $item['stream_url'] ?? null,
                        'logo' => $item['logo'] ?? $item['tvg_logo'] ?? null,
                        'tvg_id' => $item['tvg_id'] ?? null,
                        'group_title' => $item['group_title'] ?? null,
                        'extension' => $item['extension'] ?? null,
                        'rating' => $item['rating'] ?? null,
                        'description' => $item['description'] ?? null,
                        'year' => $item['year'] ?? null,
                        'is_adult' => (bool) ($item['is_adult'] ?? IptvItem::isAdultName($item['group_title'] ?? $item['name'] ?? null)),
                        'is_active' => true,
                        'is_public' => $publicationChoices->get($type.'|'.$externalId, true),
                        'raw_data' => json_encode($item['raw_data'] ?? $item),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                IptvItem::query()->insert($rows);
            }

            $playlist->forceFill([
                'status' => 'active',
                'imported_channels_count' => $counts['live'] ?? 0,
                'imported_movies_count' => $counts['movie'] ?? 0,
                'imported_series_count' => $counts['series'] ?? 0,
                'last_imported_at' => $now,
                'last_synced_at' => $now,
                'last_error' => null,
            ])->save();
        });

        return $playlist->refresh();
    }

    private function loadM3uContent(Playlist $playlist): string
    {
        if ($playlist->resolved_file_path) {
            return Storage::disk('playlists')->get($playlist->resolved_file_path);
        }

        $url = $playlist->m3u_url ?: $playlist->source_url;

        if (! $url) {
            throw ValidationException::withMessages([
                'playlist' => [__('This playlist needs an M3U URL or uploaded file before import.')],
            ]);
        }

        $this->urlSafetyService->assertSafeForImport($url);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300)
            ->withUserAgent('Mozilla/5.0 IPTV Player')
            ->accept('application/x-mpegURL, application/vnd.apple.mpegurl, text/plain, */*')
            ->get($url);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'playlist' => [__('The playlist URL could not be fetched.')],
            ]);
        }

        return (string) $response->body();
    }
}

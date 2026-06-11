<?php

namespace App\Services;

use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class XtreamImporter
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly PlaylistUrlBuilder $urlBuilder,
        private readonly UrlSafetyService $urlSafetyService,
    ) {}

    public function import(Playlist $playlist): Playlist
    {
        $serverUrl = (string) $playlist->server_url;
        $username = (string) $playlist->username;
        $password = (string) $playlist->password;

        foreach ([$serverUrl, $this->urlBuilder->buildXtreamApiUrl($serverUrl, $username, $password)] as $url) {
            $this->urlSafetyService->assertSafeForImport($url);
        }

        $this->fetch($serverUrl, $username, $password, null);

        $liveCategories = $this->fetch($serverUrl, $username, $password, 'get_live_categories');
        $liveStreams = $this->fetch($serverUrl, $username, $password, 'get_live_streams');
        $vodCategories = $this->fetch($serverUrl, $username, $password, 'get_vod_categories');
        $vodStreams = $this->fetch($serverUrl, $username, $password, 'get_vod_streams');
        $seriesCategories = $this->fetch($serverUrl, $username, $password, 'get_series_categories');
        $series = $this->fetch($serverUrl, $username, $password, 'get_series');

        return $this->saveXtreamData($playlist, [
            'live' => [$liveCategories, $liveStreams],
            'movie' => [$vodCategories, $vodStreams],
            'series' => [$seriesCategories, $series],
        ]);
    }

    private function fetch(string $serverUrl, string $username, string $password, ?string $action): array
    {
        $url = $this->urlBuilder->buildXtreamApiUrl($serverUrl, $username, $password, $action);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300)
            ->withUserAgent('Mozilla/5.0 IPTV Player')
            ->acceptJson()
            ->get($url);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'playlist' => [__('The Xtream API could not be reached.')],
            ]);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function saveXtreamData(Playlist $playlist, array $data): Playlist
    {
        $now = now();
        $counts = ['live' => 0, 'movie' => 0, 'series' => 0];

        DB::transaction(function () use ($playlist, $data, $now, &$counts): void {
            $publicationChoices = $playlist->iptvItems()
                ->get(['type', 'external_id', 'is_public'])
                ->mapWithKeys(fn (IptvItem $item): array => [
                    $item->type.'|'.$item->external_id => $item->is_public,
                ]);

            $playlist->iptvItems()->delete();
            $playlist->iptvCategories()->delete();

            $categoryIds = [];

            foreach ($data as $type => [$categories]) {
                foreach ($categories as $index => $category) {
                    $externalId = (string) ($category['category_id'] ?? $category['id'] ?? $category['name'] ?? $index);
                    $created = IptvCategory::query()->create([
                        'playlist_id' => $playlist->id,
                        'type' => $type,
                        'external_id' => $externalId,
                        'name' => (string) ($category['category_name'] ?? $category['name'] ?? 'Uncategorized'),
                        'sort_order' => (int) ($category['sort_order'] ?? $index),
                    ]);
                    $categoryIds[$type][$externalId] = $created->id;
                }
            }

            foreach ($data as $type => [, $items]) {
                foreach (array_chunk($items, self::CHUNK_SIZE) as $chunk) {
                    $rows = [];

                    foreach ($chunk as $item) {
                        $externalId = (string) ($item['stream_id'] ?? $item['series_id'] ?? $item['id'] ?? sha1(json_encode($item)));
                        $categoryExternalId = (string) ($item['category_id'] ?? '');
                        $extension = (string) ($item['container_extension'] ?? ($type === 'live' ? $playlist->output : 'mp4'));
                        $streamUrl = $this->buildStreamUrl($playlist, $type, $externalId, $extension);
                        $counts[$type]++;

                        $rows[] = [
                            'playlist_id' => $playlist->id,
                            'category_id' => $categoryIds[$type][$categoryExternalId] ?? null,
                            'type' => $type,
                            'external_id' => $externalId,
                            'name' => (string) ($item['name'] ?? $item['title'] ?? 'Untitled'),
                            'stream_url' => $streamUrl,
                            'logo' => $item['stream_icon'] ?? $item['cover'] ?? null,
                            'tvg_id' => $item['epg_channel_id'] ?? null,
                            'group_title' => null,
                            'extension' => $extension,
                            'rating' => $item['rating'] ?? null,
                            'description' => $item['plot'] ?? $item['description'] ?? null,
                            'year' => isset($item['year']) ? (string) $item['year'] : null,
                            'is_adult' => IptvItem::isAdultName(($item['name'] ?? '').' '.($item['category_name'] ?? '')),
                            'is_active' => true,
                            'is_public' => $publicationChoices->get($type.'|'.$externalId, true),
                            'raw_data' => json_encode($item),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        IptvItem::query()->insert($rows);
                    }
                }
            }

            $playlist->forceFill([
                'm3u_url' => $this->urlBuilder->buildFromXtream((string) $playlist->server_url, (string) $playlist->username, (string) $playlist->password, (string) $playlist->output),
                'status' => 'active',
                'imported_channels_count' => $counts['live'],
                'imported_movies_count' => $counts['movie'],
                'imported_series_count' => $counts['series'],
                'last_imported_at' => $now,
                'last_synced_at' => $now,
                'last_error' => null,
            ])->save();
        });

        return $playlist->refresh();
    }

    private function buildStreamUrl(Playlist $playlist, string $type, string $externalId, string $extension): string
    {
        $base = rtrim((string) $playlist->server_url, '/');
        $username = rawurlencode((string) $playlist->username);
        $password = rawurlencode((string) $playlist->password);

        return match ($type) {
            'movie' => "{$base}/movie/{$username}/{$password}/{$externalId}.{$extension}",
            'series' => "{$base}/series/{$username}/{$password}/{$externalId}.{$extension}",
            default => "{$base}/live/{$username}/{$password}/{$externalId}.{$extension}",
        };
    }
}

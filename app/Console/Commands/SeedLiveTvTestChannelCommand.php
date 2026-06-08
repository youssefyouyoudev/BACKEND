<?php

namespace App\Console\Commands;

use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SeedLiveTvTestChannelCommand extends Command
{
    protected $signature = 'live-tv:seed-test
        {--playlist= : Existing playlist ID to own the test item}
        {--url= : Browser-compatible HLS or MPEG-TS test stream URL}';

    protected $description = 'Create or update one local public Live TV test channel';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command is only available in local and testing environments.');

            return self::FAILURE;
        }

        $url = trim((string) ($this->option('url') ?: env('LIVE_TV_TEST_STREAM_URL')));

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $this->error('Provide a valid HTTP(S) stream using --url or LIVE_TV_TEST_STREAM_URL.');

            return self::FAILURE;
        }

        $playlist = $this->option('playlist')
            ? Playlist::query()->find($this->option('playlist'))
            : Playlist::query()->oldest('id')->first();

        if (! $playlist) {
            $this->error('Create or import a playlist first, or pass an existing --playlist ID.');

            return self::FAILURE;
        }

        $category = IptvCategory::query()->updateOrCreate(
            [
                'playlist_id' => $playlist->id,
                'type' => IptvItem::TYPE_LIVE,
                'external_id' => 'local-live-tv-test',
            ],
            [
                'name' => 'Local Test',
                'sort_order' => 0,
            ]
        );

        $item = IptvItem::query()->updateOrCreate(
            [
                'playlist_id' => $playlist->id,
                'type' => IptvItem::TYPE_LIVE,
                'external_id' => 'local-live-tv-test',
            ],
            [
                'category_id' => $category->id,
                'name' => 'Local Live TV Test HD',
                'stream_url' => $url,
                'group_title' => $category->name,
                'extension' => str_contains(strtolower($url), '.m3u8') ? 'm3u8' : 'stream',
                'is_adult' => false,
                'is_active' => true,
                'is_public' => true,
            ]
        );

        foreach ([
            'api-tv:categories',
            'api-tv:curated-sports-categories-v1',
            'api-tv:live-categories-v2',
        ] as $key) {
            Cache::forget($key);
        }

        $this->info("Public Live TV test channel ready (item {$item->id}).");

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Channel;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $isProduction = app()->environment('production');
        $adminEmail = config('rifimedia.admin.email');
        $adminName = config('rifimedia.admin.name');
        $adminPassword = config('rifimedia.admin.password');

        if (! $adminPassword && $isProduction) {
            throw new RuntimeException('Set RIFIMEDIA_ADMIN_PASSWORD in .env before running the production seeder.');
        }

        $adminPassword ??= 'Password123!';

        $admin = User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $this->command?->info("Admin user seeded: {$admin->email}");

        if ($isProduction) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'demo@rifimedia.test'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_USER,
                'is_active' => true,
            ]
        );

        $playlist = Playlist::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Demo Legal Playlist'],
            [
                'source_type' => Playlist::SOURCE_TYPE_URL,
                'source_url' => 'https://example.com/legal-demo.m3u8',
                'status' => 'completed',
                'is_public' => true,
                'approved_by_admin' => $admin->id,
                'approved_at' => now(),
                'last_synced_at' => now(),
                'import_summary' => [
                    'imported' => 8,
                    'updated' => 0,
                    'removed' => 0,
                    'groups' => ['News', 'Sports', 'Kids', 'Movies', 'Documentary', 'Entertainment'],
                    'total_channels' => 8,
                ],
            ]
        );

        $channels = [
            ['name' => 'RiFi News 24', 'group_title' => 'News', 'stream_url' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8'],
            ['name' => 'RiFi Sports Central', 'group_title' => 'Sports', 'stream_url' => 'https://mojenozki.github.io/hls-vod/media.m3u8'],
            ['name' => 'RiFi Kids Zone', 'group_title' => 'Kids', 'stream_url' => 'https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8'],
            ['name' => 'RiFi Cinema Max', 'group_title' => 'Movies', 'stream_url' => 'https://devstreaming-cdn.apple.com/videos/streaming/examples/img_bipbop_adv_example_ts/master.m3u8'],
            ['name' => 'RiFi Nature', 'group_title' => 'Documentary', 'stream_url' => 'https://cph-p2p-msl.akamaized.net/hls/live/2000341/test/master.m3u8'],
            ['name' => 'RiFi World Live', 'group_title' => 'News', 'stream_url' => 'https://test-streams.mux.dev/test_001/stream.m3u8'],
            ['name' => 'RiFi Arena', 'group_title' => 'Sports', 'stream_url' => 'https://playertest.longtailvideo.com/adaptive/wowzaid3/playlist.m3u8'],
            ['name' => 'RiFi Family', 'group_title' => 'Entertainment', 'stream_url' => 'https://content.jwplatform.com/manifests/yp34SRmf.m3u8'],
        ];

        foreach ($channels as $index => $channel) {
            Channel::query()->updateOrCreate(
                ['playlist_id' => $playlist->id, 'stream_hash' => sha1(strtolower($channel['stream_url']))],
                [
                    'name' => $channel['name'],
                    'tvg_id' => str($channel['name'])->slug()->toString(),
                    'logo' => null,
                    'group_title' => $channel['group_title'],
                    'stream_url' => $channel['stream_url'],
                    'stream_type' => 'hls',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'is_featured' => true,
                    'featured_rank' => $index + 1,
                    'metadata' => ['duration' => -1],
                ]
            );
        }

        foreach ([
            'legal_notice' => json_encode('Users are solely responsible for verifying the legality and licensing of every playlist URL, uploaded file, and stream they import into RiFiMedia.', JSON_THROW_ON_ERROR),
            'homepage_featured_groups' => json_encode(['News', 'Sports', 'Movies', 'Kids'], JSON_THROW_ON_ERROR),
            'allow_public_playlists' => json_encode(true, JSON_THROW_ON_ERROR),
            'allow_url_imports' => json_encode(true, JSON_THROW_ON_ERROR),
            'brand_tagline' => json_encode('A dark, premium IPTV web player for legal playlists.', JSON_THROW_ON_ERROR),
            'maintenance_banner' => json_encode('', JSON_THROW_ON_ERROR),
        ] as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}

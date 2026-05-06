<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);
        $streamUrl = fake()->url().'/'.fake()->slug().'.m3u8';

        return [
            'playlist_id' => Playlist::factory(),
            'tvg_id' => fake()->slug(),
            'name' => ucwords($name),
            'logo' => fake()->imageUrl(320, 180, 'abstract'),
            'group_title' => fake()->randomElement(['News', 'Sports', 'Kids', 'Movies']),
            'stream_url' => $streamUrl,
            'stream_type' => 'hls',
            'stream_hash' => sha1(strtolower($streamUrl)),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'is_featured' => false,
            'featured_rank' => null,
            'metadata' => [
                'duration' => -1,
            ],
        ];
    }
}

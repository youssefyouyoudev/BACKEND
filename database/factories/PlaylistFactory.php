<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playlist>
 */
class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' Playlist',
            'input_type' => Playlist::INPUT_TYPE_REMOTE_URL,
            'source_type' => Playlist::SOURCE_TYPE_URL,
            'm3u_url' => fake()->url(),
            'source_url' => fn (array $attributes) => $attributes['m3u_url'],
            'status' => 'completed',
            'last_synced_at' => now(),
            'is_public' => false,
            'approved_by_admin' => null,
            'approved_at' => null,
            'import_summary' => [
                'imported' => 2,
                'updated' => 0,
                'removed' => 0,
                'groups' => ['News'],
                'total_channels' => 2,
            ],
        ];
    }
}

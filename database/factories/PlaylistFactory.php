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
            'source_type' => Playlist::SOURCE_TYPE_URL,
            'source_url' => fake()->url(),
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

<?php

use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use App\Models\User;
use App\Services\PlaylistImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdminIptvItem(array $attributes = []): IptvItem
{
    $owner = User::factory()->create();
    $playlist = Playlist::factory()->create([
        'user_id' => $owner->id,
        'is_public' => true,
        'approved_at' => now(),
    ]);
    $category = IptvCategory::query()->create([
        'playlist_id' => $playlist->id,
        'type' => $attributes['type'] ?? IptvItem::TYPE_LIVE,
        'name' => $attributes['group_title'] ?? 'Sports',
    ]);

    return IptvItem::query()->create(array_merge([
        'playlist_id' => $playlist->id,
        'category_id' => $category->id,
        'type' => IptvItem::TYPE_LIVE,
        'external_id' => fake()->uuid(),
        'name' => 'beIN Sports 1 HD',
        'stream_url' => 'https://streams.example.com/live.m3u8',
        'group_title' => 'Sports',
        'extension' => 'm3u8',
        'is_active' => true,
        'is_public' => true,
    ], $attributes));
}

it('only lets administrators manage IPTV item visibility', function () {
    $user = User::factory()->create();
    $item = createAdminIptvItem();

    $this->actingAs($user)
        ->get(route('admin.iptv-items.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patchJson(route('admin.iptv-items.visibility', $item), ['is_public' => false])
        ->assertForbidden();
});

it('searches and filters IPTV items through the AJAX catalog', function () {
    $admin = User::factory()->admin()->create();
    createAdminIptvItem(['name' => 'Search Target Sports']);
    createAdminIptvItem([
        'type' => IptvItem::TYPE_MOVIE,
        'name' => 'Unrelated Movie',
        'group_title' => 'Movies',
        'is_public' => false,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.iptv-items.index', [
            'q' => 'Search Target',
            'type' => IptvItem::TYPE_LIVE,
            'visibility' => 'public',
        ]))
        ->assertSuccessful()
        ->assertJsonPath('summary.filtered', 1);

    expect($response->json('rows'))
        ->toContain('Search Target Sports')
        ->toContain('Source available')
        ->toContain('Ready')
        ->toContain('SD')
        ->not->toContain('Unrelated Movie');
});

it('toggles public visibility with AJAX and validates the payload', function () {
    $admin = User::factory()->admin()->create();
    $item = createAdminIptvItem();

    $this->actingAs($admin)
        ->patchJson(route('admin.iptv-items.visibility', $item), ['is_public' => false])
        ->assertSuccessful()
        ->assertJsonPath('item.id', $item->id)
        ->assertJsonPath('item.is_public', false);

    expect($item->refresh()->is_public)->toBeFalse();

    $this->actingAs($admin)
        ->patchJson(route('admin.iptv-items.visibility', $item), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('is_public');
});

it('lets administrators make every IPTV item public or hidden at once', function () {
    $admin = User::factory()->admin()->create();
    $publicItem = createAdminIptvItem(['name' => 'Public Item', 'is_public' => true]);
    $hiddenItem = createAdminIptvItem(['name' => 'Hidden Item', 'is_public' => false]);

    $this->actingAs($admin)
        ->patchJson(route('admin.iptv-items.visibility.all'), ['is_public' => false])
        ->assertSuccessful()
        ->assertJsonPath('updated', 1)
        ->assertJsonPath('is_public', false);

    expect($publicItem->refresh()->is_public)->toBeFalse()
        ->and($hiddenItem->refresh()->is_public)->toBeFalse();

    $this->actingAs($admin)
        ->patchJson(route('admin.iptv-items.visibility.all'), ['is_public' => true])
        ->assertSuccessful()
        ->assertJsonPath('updated', 2)
        ->assertJsonPath('is_public', true);

    expect($publicItem->refresh()->is_public)->toBeTrue()
        ->and($hiddenItem->refresh()->is_public)->toBeTrue();
});

it('prevents non-administrators from changing all IPTV item visibility', function () {
    $user = User::factory()->create();
    createAdminIptvItem();

    $this->actingAs($user)
        ->patchJson(route('admin.iptv-items.visibility.all'), ['is_public' => false])
        ->assertForbidden();
});

it('removes hidden items from watch and live TV public endpoints', function () {
    $item = createAdminIptvItem([
        'name' => 'beIN Sports Hidden Test HD',
        'is_public' => false,
    ]);

    $this->get('/watch/search?q=Hidden+Test')
        ->assertSuccessful()
        ->assertDontSee('beIN Sports Hidden Test HD');

    $this->getJson('/api/watch/search?q=Hidden+Test')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->getJson('/api/tv/channels?search=Hidden+Test')
        ->assertSuccessful()
        ->assertJsonCount(0, 'channels');

    $this->get(route('watch.item', $item))->assertNotFound();
    $this->getJson("/api/tv/channels/{$item->id}")->assertNotFound();
});

it('preserves an item visibility choice when an M3U playlist is reimported', function () {
    $item = createAdminIptvItem([
        'external_id' => 'stable-channel',
        'name' => 'Stable Channel',
        'is_public' => false,
    ]);

    app(PlaylistImporter::class)->saveParsedItems($item->playlist, [[
        'type' => IptvItem::TYPE_LIVE,
        'external_id' => 'stable-channel',
        'name' => 'Stable Channel Updated',
        'stream_url' => 'https://streams.example.com/stable.m3u8',
        'group_title' => 'Sports',
        'extension' => 'm3u8',
    ]]);

    $reimported = IptvItem::query()
        ->where('playlist_id', $item->playlist_id)
        ->where('external_id', 'stable-channel')
        ->firstOrFail();

    expect($reimported->name)->toBe('Stable Channel Updated')
        ->and($reimported->is_public)->toBeFalse();
});

it('creates a safe local public test channel through the seed command', function () {
    $playlist = Playlist::factory()->create();

    $this->artisan('live-tv:seed-test', [
        '--playlist' => $playlist->id,
        '--url' => 'https://streams.example.com/local-test.m3u8',
    ])->assertSuccessful();

    $item = IptvItem::query()
        ->where('playlist_id', $playlist->id)
        ->where('external_id', 'local-live-tv-test')
        ->firstOrFail();

    expect($item->name)->toBe('Local Live TV Test HD')
        ->and($item->is_active)->toBeTrue()
        ->and($item->is_public)->toBeTrue()
        ->and($item->extension)->toBe('m3u8');
});

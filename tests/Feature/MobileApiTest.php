<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Playlist;
use App\Models\User;
use App\Services\TheSportsDbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

function publicPlaylist(): Playlist
{
    return Playlist::factory()->create([
        'status' => 'ready',
        'is_public' => true,
        'approved_at' => now(),
    ]);
}

function createPublicChannel(array $attributes = []): Channel
{
    $category = $attributes['category_id'] ?? Category::query()->create([
        'name' => 'Sports',
        'slug' => 'sports',
        'is_active' => true,
    ])->id;

    return Channel::factory()->for(publicPlaylist())->create([
        'name' => 'Rifi Sports HD',
        'slug' => 'rifi-sports-hd',
        'group_title' => 'Sports',
        'category_id' => $category,
        'stream_url' => 'https://streams.example.com/rifi-sports.m3u8',
        'stream_hash' => sha1('https://streams.example.com/rifi-sports.m3u8'),
        'is_live' => true,
        'is_active' => true,
        'is_featured' => true,
        'featured_rank' => 1,
        'metadata' => ['quality' => 'HD'],
        ...$attributes,
    ]);
}

function publishedArticle(array $attributes = []): Article
{
    $category = Category::query()->firstOrCreate(
        ['slug' => 'football'],
        ['name' => 'Football', 'is_active' => true]
    );

    return Article::query()->create([
        'category_id' => $category->id,
        'author_id' => User::factory()->create()->id,
        'title' => 'Transfer Window Live',
        'slug' => 'transfer-window-live',
        'excerpt' => 'Latest football transfer news.',
        'body' => 'Full football transfer story.',
        'featured_image' => 'https://images.example.com/news.jpg',
        'status' => 'published',
        'published_at' => now()->subHour(),
        ...$attributes,
    ]);
}

it('lists channels with pagination, status, quality, and images', function (): void {
    createPublicChannel();

    $this->getJson('/api/channels?q=sports&category=sports&quality=HD')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Rifi Sports')
        ->assertJsonPath('data.0.status.is_live', true)
        ->assertJsonPath('data.0.quality.hd', true)
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'logo_url', 'image_url', 'status', 'quality', 'links'],
            ],
            'links',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('lists featured channels and channel categories', function (): void {
    createPublicChannel();

    $this->getJson('/api/channels/featured')
        ->assertOk()
        ->assertJsonPath('data.0.status.is_featured', true);

    $this->getJson('/api/channels/categories')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'sports')
        ->assertJsonPath('data.0.channels_count', 1);
});

it('shows a channel with signed player urls only for approved public channels', function (): void {
    $channel = createPublicChannel();
    $private = Channel::factory()->create([
        'is_live' => true,
        'is_active' => true,
    ]);

    $this->getJson("/api/channels/{$channel->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $channel->id)
        ->assertJsonStructure([
            'data' => ['player_url', 'stream_url', 'sources'],
        ]);

    $this->getJson("/api/channels/{$private->id}")
        ->assertNotFound()
        ->assertJsonPath('message', 'Channel not found.');
});

it('lists channels for a category slug', function (): void {
    createPublicChannel();

    $this->getJson('/api/categories/sports/channels')
        ->assertOk()
        ->assertJsonPath('data.0.category.slug', 'sports');
});

it('lists and shows published news with image urls', function (): void {
    publishedArticle();

    $this->getJson('/api/news?q=transfer')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'transfer-window-live')
        ->assertJsonPath('data.0.image_url', 'https://images.example.com/news.jpg');

    $this->getJson('/api/news/transfer-window-live')
        ->assertOk()
        ->assertJsonPath('data.body', 'Full football transfer story.');
});

it('lists configured leagues', function (): void {
    $this->getJson('/api/leagues')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'premier-league')
        ->assertJsonPath('data.0.status.is_active', true);
});

it('searches channels, news, and leagues', function (): void {
    createPublicChannel();
    publishedArticle();

    $this->getJson('/api/search?q=football')
        ->assertOk()
        ->assertJsonPath('data.query', 'football')
        ->assertJsonStructure([
            'data' => ['channels', 'news', 'leagues'],
            'meta' => ['total'],
        ]);
});

it('keeps existing football api endpoints returning json', function (): void {
    $match = [
        'id' => '12345',
        'home_team' => ['name' => 'Home'],
        'away_team' => ['name' => 'Away'],
    ];

    $this->mock(TheSportsDbService::class, function (MockInterface $mock) use ($match): void {
        $mock->shouldReceive('getTopLeagueMatchesByDate')->twice()->andReturn([$match]);
        $mock->shouldReceive('getUpcomingTopLeagueMatches')->once()->andReturn([$match]);
        $mock->shouldReceive('getRecentTopLeagueResults')->once()->andReturn([$match]);
        $mock->shouldReceive('getEventDetails')->once()->with('12345')->andReturn($match);
        $mock->shouldReceive('getEventTvChannels')->once()->with('12345')->andReturn([
            ['channel' => 'Rifi Sports', 'country' => 'MA'],
        ]);
    });

    $this->getJson('/football/api/today')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/football/api/date?date=2026-06-02')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/football/api/upcoming')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/football/api/results')->assertOk()->assertJsonPath('success', true);
    $this->get('/football/api/event/12345')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/football/api/event/12345/tv')->assertOk()->assertJsonPath('data.0.channel', 'Rifi Sports');
});

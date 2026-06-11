<?php

use App\Models\Channel;
use App\Models\Playlist;
use App\Models\User;
use App\Models\WorldCupMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the public home page with stored channels', function () {
    $playlist = Playlist::factory()->create([
        'status' => 'ready',
        'is_public' => true,
        'approved_at' => now(),
    ]);

    Channel::factory()->for($playlist)->create([
        'name' => 'RiFi Sports Central',
        'group_title' => 'Sports',
        'stream_url' => 'https://streams.example.com/sports.m3u8',
        'stream_hash' => sha1('https://streams.example.com/sports.m3u8'),
        'is_active' => true,
    ]);
    WorldCupMatch::query()->create([
        'home_team' => 'Morocco',
        'away_team' => 'Brazil',
        'stage' => 'Group Stage',
        'kickoff_at' => now()->addDay(),
        'morocco_kickoff_at' => now()->addDay(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('RiFi Sports Central')
        ->assertSee('Your Match Day Starts Here')
        ->assertSee('World Cup 2026 Coverage')
        ->assertSee('Morocco')
        ->assertSee('Brazil')
        ->assertSee('images/flags/ma.svg', false)
        ->assertSee('images/flags/br.svg', false);
});

it('renders localized landing metadata and rtl Arabic', function () {
    $playlist = Playlist::factory()->create([
        'status' => 'ready',
        'is_public' => true,
        'approved_at' => now(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('RiFiTV - Match Schedules, Channels and World Cup 2026')
        ->assertSee('og:image', false);

    $this->get(route('language.switch', 'ar'))
        ->assertRedirect();

    $this->get('/')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('يوم المباراة يبدأ من هنا')
        ->assertSee('تغطية كأس العالم 2026');
});

it('rejects unsupported locales', function () {
    $this->get('/lang/fr')->assertNotFound();
});

it('requires authentication for the admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('allows an admin to reach the dashboard', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Playlist ingestion and channel publishing.');
});

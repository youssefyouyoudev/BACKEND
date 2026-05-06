<?php

use App\Models\Channel;
use App\Models\Playlist;
use App\Models\User;
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

    $this->get('/')
        ->assertOk()
        ->assertSee('RiFi Sports Central')
        ->assertSee('Categories');
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

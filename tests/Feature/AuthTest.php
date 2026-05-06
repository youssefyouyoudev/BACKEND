<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user and returns a sanctum token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'RiFi User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('user.email', 'user@example.com')
        ->assertJsonStructure([
            'message',
            'token',
            'user' => ['id', 'name', 'email', 'role'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
        'role' => User::ROLE_USER,
    ]);
});

it('prevents inactive users from logging in', function () {
    User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => 'Password123!',
        'is_active' => false,
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'inactive@example.com',
        'password' => 'Password123!',
    ])->assertForbidden();
});

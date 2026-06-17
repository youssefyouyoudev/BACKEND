<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the aggregate stream health page to admins', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.stream-health'))
        ->assertSuccessful()
        ->assertSee('Stream Health')
        ->assertSee('Public live IPTV');
});

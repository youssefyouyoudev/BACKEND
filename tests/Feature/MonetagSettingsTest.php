<?php

use App\Models\AdSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to update ad settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($admin)
        ->put(route('admin.monetization.update'), [
            'settings' => [
                [
                    'placement_key' => 'sticky_mobile',
                    'enabled' => '1',
                    'script_code' => '<script async src="https://example.com/tag.js"></script>',
                    'direct_link_url' => 'https://example.com/offer',
                    'device' => 'mobile',
                    'frequency_seconds' => 1200,
                    'max_per_session' => 1,
                    'test_mode' => '1',
                ],
            ],
        ])
        ->assertRedirect();

    $setting = AdSetting::query()->where('placement_key', 'sticky_mobile')->firstOrFail();

    expect($setting)
        ->enabled->toBeTrue()
        ->direct_link_url->toBe('https://example.com/offer')
        ->frequency_seconds->toBe(1200)
        ->max_per_session->toBe(1)
        ->test_mode->toBeTrue();
});

it('does not render disabled public ad slots', function () {
    AdSetting::query()->create([
        'provider' => AdSetting::PROVIDER_MONETAG,
        'placement_key' => 'sticky_mobile',
        'enabled' => false,
        'device' => 'mobile',
        'frequency_seconds' => 300,
        'max_per_session' => 1,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('data-ad-slot="sticky_mobile"', false);
});

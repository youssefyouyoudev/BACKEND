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

it('exposes a single centralized public ad config', function () {
    $config = AdSetting::publicConfig();

    expect($config['rifimediaPopup']['url'])->toBe('https://rifimedia.com')
        ->and($config['smartlinkUrl'])->toBe('https://omg10.com/4/11137969')
        ->and(collect($config['monetag'])->pluck('id')->duplicates()->all())->toBe([])
        ->and(collect($config['monetag'])->pluck('zone')->all())->toContain('11137947', '11137952', '11137954');
});

it('renders Monetag source URLs in the public layout config', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('al5sm.com', false)
        ->assertSee('nap5k.com', false)
        ->assertSee('n6wxm.com', false)
        ->assertSee('11137947', false)
        ->assertSee('11137952', false)
        ->assertSee('11137954', false)
        ->assertSee('omg10.com', false);
});

it('installs one public Monetag service worker at the root', function () {
    $path = public_path('sw.js');

    expect($path)->toBeFile();

    $contents = file_get_contents($path);

    expect($contents)
        ->toContain('"zoneId": 11137945')
        ->toContain("importScripts('https://3nbf4.com/act/files/service-worker.min.js?r=sw')");
});

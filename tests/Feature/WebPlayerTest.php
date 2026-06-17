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
        ->assertSee('Football scores, schedules, news, and World Cup 2026 guide in Morocco time.')
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
        ->assertSee('RiFiTV - Football Scores, Schedules, World Cup 2026 and TV Guide')
        ->assertSee('og:image', false);

    $this->get(route('language.switch', 'ar'))
        ->assertRedirect();

    $this->get('/')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('RiFiTV');
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

it('keeps blocking ad scripts opt-in and never loads ads in admin', function () {
    config()->set('ads.enabled', true);
    config()->set('ads.runtime_enabled', false);

    $publicResponse = $this->get('/')->assertOk();
    $content = $publicResponse->getContent();

    foreach ([
        'n6wxm.com/vignette.min.js',
        'nap5k.com/tag.min.js',
        'al5sm.com/tag.min.js',
        '5gvci.com/act/files/tag.min.js?z=11137945',
        'quge5.com/88/tag.min.js',
    ] as $script) {
        expect(substr_count($content, $script))->toBe(0);
    }

    $publicResponse
        ->assertSee('data-ad-placement="home_after_hero"', false)
        ->assertDontSee('sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-forms allow-top-navigation-by-user-activation"', false)
        ->assertSee('rel="nofollow sponsored noopener noreferrer"', false);

    config()->set('ads.runtime_enabled', true);
    $runtimeContent = $this->get('/')->assertOk()->getContent();

    foreach ([
        'n6wxm.com/vignette.min.js',
        'nap5k.com/tag.min.js',
        'al5sm.com/tag.min.js',
        '5gvci.com/act/files/tag.min.js?z=11137945',
        'quge5.com/88/tag.min.js',
    ] as $script) {
        expect(substr_count($runtimeContent, $script))->toBe(1);
    }

    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('n6wxm.com', false)
        ->assertDontSee('data-ad-placement=', false);
});

it('can disable all public ads through configuration', function () {
    config()->set('ads.enabled', false);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('n6wxm.com', false)
        ->assertDontSee('data-ad-placement=', false);
});

it('keeps ad scripts off error pages and uses the ad-compatible CSP by default', function () {
    config()->set('ads.enabled', true);

    $response = $this->get('/missing-ad-test-page')->assertNotFound();
    $policy = $response->headers->get('Content-Security-Policy');

    $response->assertDontSee('n6wxm.com', false);

    expect($policy)
        ->toContain("default-src 'self' https: data: blob:")
        ->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:")
        ->toContain("connect-src 'self' https: wss: blob:")
        ->toContain("frame-src 'self' https: blob:")
        ->toContain("media-src 'self' https: blob: data:")
        ->toContain("worker-src 'self' blob:");
});

it('can switch to the stricter CSP while keeping browser video playback support', function () {
    config()->set('security.csp_ads_compatible', false);

    $policy = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

    expect($policy)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://static.cloudflareinsights.com blob:")
        ->toContain("connect-src 'self' https: wss: blob:")
        ->toContain("media-src 'self' https: blob: data:")
        ->toContain("worker-src 'self' blob:")
        ->not->toContain('n6wxm.com')
        ->not->toContain('nap5k.com');
});

it('emits a strict permissions policy without allowing bluetooth', function () {
    $policy = $this->get('/')->assertOk()->headers->get('Permissions-Policy');

    expect($policy)
        ->toContain('bluetooth=()')
        ->not->toContain('bluetooth=(self)');
});

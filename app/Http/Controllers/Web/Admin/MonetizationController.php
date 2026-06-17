<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateAdSettingsRequest;
use App\Models\AdSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonetizationController extends Controller
{
    public function edit(): View
    {
        $settings = collect(AdSetting::PLACEMENTS)
            ->map(function (string $label, string $key): AdSetting {
                $defaults = $this->defaultsFor($key);

                return AdSetting::query()->firstOrNew([
                    'provider' => AdSetting::PROVIDER_MONETAG,
                    'placement_key' => $key,
                ], [
                    'enabled' => $defaults['enabled'],
                    'script_code' => $defaults['script_code'],
                    'direct_link_url' => $defaults['direct_link_url'],
                    'device' => $defaults['device'],
                    'frequency_seconds' => $defaults['frequency_seconds'],
                    'max_per_session' => $defaults['max_per_session'],
                ]);
            });

        return view('admin.monetization.edit', [
            'placements' => AdSetting::PLACEMENTS,
            'settings' => $settings,
        ]);
    }

    public function update(UpdateAdSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $row) {
            AdSetting::query()->updateOrCreate([
                'provider' => AdSetting::PROVIDER_MONETAG,
                'placement_key' => $row['placement_key'],
            ], [
                'enabled' => (bool) ($row['enabled'] ?? false),
                'script_code' => $row['script_code'] ?? null,
                'direct_link_url' => $row['direct_link_url'] ?? null,
                'device' => $row['device'],
                'frequency_seconds' => (int) $row['frequency_seconds'],
                'max_per_session' => (int) $row['max_per_session'],
                'test_mode' => (bool) ($row['test_mode'] ?? false),
            ]);
        }

        AdSetting::forgetCache();

        return back()->with('status', __('Monetag settings saved.'));
    }

    private function defaultsFor(string $key): array
    {
        return match ($key) {
            'zone_11137947' => [
                'enabled' => true,
                'script_code' => 'zone=11137947; src=https://al5sm.com/tag.min.js',
                'direct_link_url' => null,
                'device' => 'all',
                'frequency_seconds' => 300,
                'max_per_session' => 1,
            ],
            'zone_11137952' => [
                'enabled' => true,
                'script_code' => 'zone=11137952; src=https://nap5k.com/tag.min.js',
                'direct_link_url' => null,
                'device' => 'all',
                'frequency_seconds' => 300,
                'max_per_session' => 1,
            ],
            'vignette_11137954' => [
                'enabled' => true,
                'script_code' => 'zone=11137954; src=https://n6wxm.com/vignette.min.js',
                'direct_link_url' => null,
                'device' => 'all',
                'frequency_seconds' => 1800,
                'max_per_session' => 1,
            ],
            'rifimedia_popup' => [
                'enabled' => true,
                'script_code' => json_encode([
                    'title' => config('ads.rifimedia_popup.title'),
                    'message' => config('ads.rifimedia_popup.message'),
                ], JSON_UNESCAPED_UNICODE),
                'direct_link_url' => config('ads.rifimedia_popup.url'),
                'device' => 'all',
                'frequency_seconds' => (int) config('ads.rifimedia_popup.frequency_hours') * 3600,
                'max_per_session' => 1,
            ],
            'smartlink' => [
                'enabled' => true,
                'script_code' => null,
                'direct_link_url' => config('ads.smartlink_url'),
                'device' => 'all',
                'frequency_seconds' => 1800,
                'max_per_session' => 1,
            ],
            'sticky_mobile' => [
                'enabled' => true,
                'script_code' => null,
                'direct_link_url' => config('ads.smartlink_url'),
                'device' => 'mobile',
                'frequency_seconds' => 43200,
                'max_per_session' => 1,
            ],
            'desktop_sidebar' => [
                'enabled' => true,
                'script_code' => null,
                'direct_link_url' => config('ads.smartlink_url'),
                'device' => 'desktop',
                'frequency_seconds' => 300,
                'max_per_session' => 10,
            ],
            'between_matches' => [
                'enabled' => true,
                'script_code' => null,
                'direct_link_url' => config('ads.smartlink_url'),
                'device' => 'all',
                'frequency_seconds' => 300,
                'max_per_session' => 10,
            ],
            'watch_page_ads' => [
                'enabled' => true,
                'script_code' => 'Controls sidebar/below-player ads only. Ads are never injected inside the player.',
                'direct_link_url' => config('ads.smartlink_url'),
                'device' => 'all',
                'frequency_seconds' => 300,
                'max_per_session' => 10,
            ],
            'embed_page_ads' => [
                'enabled' => false,
                'script_code' => 'Keep disabled unless an embed-only ad policy is approved.',
                'direct_link_url' => null,
                'device' => 'all',
                'frequency_seconds' => 300,
                'max_per_session' => 1,
            ],
            default => [
                'enabled' => false,
                'script_code' => null,
                'direct_link_url' => null,
                'device' => str_contains($key, 'mobile') ? 'mobile' : 'all',
                'frequency_seconds' => in_array($key, ['vignette', 'smartlink'], true) ? 1800 : 300,
                'max_per_session' => in_array($key, ['vignette', 'smartlink'], true) ? 1 : 10,
            ],
        };
    }
}

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
                return AdSetting::query()->firstOrNew([
                    'provider' => AdSetting::PROVIDER_MONETAG,
                    'placement_key' => $key,
                ], [
                    'device' => str_contains($key, 'mobile') ? 'mobile' : 'all',
                    'frequency_seconds' => in_array($key, ['vignette', 'smartlink'], true) ? 1800 : 300,
                    'max_per_session' => in_array($key, ['vignette', 'smartlink'], true) ? 1 : 10,
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
}

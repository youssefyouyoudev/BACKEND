<?php

namespace App\Services;

use App\Models\AppSetting;

class AppSettingsService
{
    public const DEFAULTS = [
        'legal_notice' => 'Users are responsible for ensuring that every playlist, stream URL, and uploaded file they add to RiFiMedia is legal, licensed, and authorized for their use.',
        'homepage_featured_groups' => ['News', 'Sports', 'Kids'],
        'allow_public_playlists' => true,
        'allow_url_imports' => true,
        'brand_tagline' => 'Your legal live TV command center.',
        'maintenance_banner' => '',
    ];

    public function all(): array
    {
        $stored = AppSetting::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')
            ->all();

        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = array_key_exists($key, $stored)
                ? $this->castStoredValue($stored[$key], $default)
                : $default;
        }

        return $settings;
    }

    public function update(array $values): array
    {
        $current = $this->all();
        $merged = array_merge($current, $values);

        foreach ($merged as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->serializeValue($value)]
            );
        }

        return $this->all();
    }

    private function serializeValue(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function castStoredValue(?string $storedValue, mixed $default): mixed
    {
        if ($storedValue === null) {
            return $default;
        }

        $decoded = json_decode($storedValue, true);

        return $decoded ?? $default;
    }
}

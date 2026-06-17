<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdSetting extends Model
{
    public const PROVIDER_MONETAG = 'monetag';

    public const PLACEMENTS = [
        'multitag' => 'Monetag Multitag',
        'in_page_push' => 'In-Page Push',
        'vignette' => 'Vignette',
        'header_banner' => 'Header banner',
        'between_matches' => 'Between match sections',
        'desktop_sidebar' => 'Desktop sidebar',
        'sticky_mobile' => 'Sticky mobile',
        'smartlink' => 'SmartLink CTA',
    ];

    protected $fillable = [
        'provider',
        'placement_key',
        'script_code',
        'direct_link_url',
        'enabled',
        'device',
        'frequency_seconds',
        'max_per_session',
        'test_mode',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'frequency_seconds' => 'integer',
            'max_per_session' => 'integer',
            'test_mode' => 'boolean',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public static function forPlacement(string $placement): ?self
    {
        if (! Schema::hasTable('ad_settings')) {
            return null;
        }

        return Cache::remember(
            "ads:monetag:{$placement}",
            now()->addMinutes(5),
            fn () => self::query()
                ->where('provider', self::PROVIDER_MONETAG)
                ->where('placement_key', $placement)
                ->first()
        );
    }

    public static function enabledForPlacement(string $placement): ?self
    {
        $setting = self::forPlacement($placement);

        return $setting?->enabled ? $setting : null;
    }

    public static function forgetCache(): void
    {
        foreach (array_keys(self::PLACEMENTS) as $placement) {
            Cache::forget("ads:monetag:{$placement}");
        }
    }
}

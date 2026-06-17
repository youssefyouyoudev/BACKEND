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
        'zone_11137947' => 'Monetag tag zone 11137947',
        'zone_11137952' => 'Monetag tag zone 11137952',
        'vignette_11137954' => 'Monetag vignette zone 11137954',
        'rifimedia_popup' => 'RifiMedia popup',
        'header_banner' => 'Header banner',
        'between_matches' => 'Between match sections',
        'desktop_sidebar' => 'Desktop sidebar',
        'sticky_mobile' => 'Sticky mobile',
        'watch_page_ads' => 'Watch page ads',
        'embed_page_ads' => 'Embed page ads',
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

    public static function publicConfig(): array
    {
        $enabled = (bool) config('ads.enabled');
        $rifimediaPopup = self::forPlacement('rifimedia_popup');
        $smartlink = self::forPlacement('smartlink');
        $stickyMobile = self::forPlacement('sticky_mobile');
        $desktopSidebar = self::forPlacement('desktop_sidebar');
        $betweenSections = self::forPlacement('between_matches');
        $watchPageAds = self::forPlacement('watch_page_ads');
        $embedPageAds = self::forPlacement('embed_page_ads');

        $popupCopy = self::decodeJsonSetting($rifimediaPopup?->script_code);

        return [
            'enabled' => $enabled,
            'environment' => app()->environment(),
            'debug' => app()->environment('local'),
            'isAdmin' => request()->is('admin*'),
            'isEmbed' => request()->routeIs('matches.embed') || request()->is('embed*') || request()->is('player*'),
            'isAuthPage' => request()->routeIs('admin.login') || request()->is('login*', 'register*', 'password*'),
            'isWatchPage' => request()->routeIs('matches.watch') || request()->is('match/*/watch'),
            'disableAdsOnWatchPage' => $watchPageAds?->enabled === false,
            'disableAdsOnEmbedPage' => ! ($embedPageAds?->enabled ?? false),
            'smartlinkUrl' => $smartlink?->direct_link_url ?: config('ads.smartlink_url'),
            'placements' => [
                'stickyMobile' => $stickyMobile?->enabled ?? true,
                'desktopSidebar' => $desktopSidebar?->enabled ?? true,
                'betweenSections' => $betweenSections?->enabled ?? true,
            ],
            'rifimediaPopup' => [
                'enabled' => $rifimediaPopup?->enabled ?? (bool) config('ads.rifimedia_popup.enabled'),
                'title' => $popupCopy['title'] ?? config('ads.rifimedia_popup.title'),
                'message' => $popupCopy['message'] ?? config('ads.rifimedia_popup.message'),
                'url' => $rifimediaPopup?->direct_link_url ?: config('ads.rifimedia_popup.url'),
                'frequencyHours' => max(1, (int) (($rifimediaPopup?->frequency_seconds ?: 0) / 3600) ?: config('ads.rifimedia_popup.frequency_hours')),
            ],
            'monetag' => [
                [
                    'id' => 'monetag-zone-11137947',
                    'zone' => config('ads.monetag.zone_11137947.zone'),
                    'src' => config('ads.monetag.zone_11137947.src'),
                    'enabled' => self::forPlacement('zone_11137947')?->enabled ?? (bool) config('ads.monetag.zone_11137947.enabled'),
                    'heavy' => false,
                ],
                [
                    'id' => 'monetag-zone-11137952',
                    'zone' => config('ads.monetag.zone_11137952.zone'),
                    'src' => config('ads.monetag.zone_11137952.src'),
                    'enabled' => self::forPlacement('zone_11137952')?->enabled ?? (bool) config('ads.monetag.zone_11137952.enabled'),
                    'heavy' => false,
                ],
                [
                    'id' => 'monetag-vignette-11137954',
                    'zone' => config('ads.monetag.vignette_11137954.zone'),
                    'src' => config('ads.monetag.vignette_11137954.src'),
                    'enabled' => self::forPlacement('vignette_11137954')?->enabled ?? (bool) config('ads.monetag.vignette_11137954.enabled'),
                    'heavy' => true,
                    'delayMs' => 30000,
                ],
            ],
        ];
    }

    private static function decodeJsonSetting(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

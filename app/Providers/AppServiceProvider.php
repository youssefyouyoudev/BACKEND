<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');

        Paginator::defaultView('components.pagination');

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('playlists', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('streams', function (Request $request) {
            return Limit::perMinute(90)->by($request->ip());
        });

        View::composer('*', function ($view): void {
            $view->with('appSettings', $this->resolveSharedSettings());
        });
    }

    /**
     * @return array<string, string>
     */
    private function resolveSharedSettings(): array
    {
        $defaults = [
            'legal_notice' => 'Users are responsible for the legality and licensing of every playlist URL and stream they watch through RiFi Media TV.',
            'brand_tagline' => 'Stream your own playlists with speed, clarity, and control.',
            'maintenance_banner' => '',
        ];

        if (! Schema::hasTable('app_settings')) {
            return $defaults;
        }

        $settings = AppSetting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->map(function ($value) {
                if (! is_string($value)) {
                    return $value;
                }

                try {
                    return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    return $value;
                }
            })
            ->all();

        return [
            'legal_notice' => (string) ($settings['legal_notice'] ?? $defaults['legal_notice']),
            'brand_tagline' => (string) ($settings['brand_tagline'] ?? $defaults['brand_tagline']),
            'maintenance_banner' => (string) ($settings['maintenance_banner'] ?? $defaults['maintenance_banner']),
        ];
    }
}

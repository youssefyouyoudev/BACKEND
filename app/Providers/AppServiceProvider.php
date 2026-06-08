<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        if ($this->shouldForceHttps()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('components.pagination');

        RateLimiter::for('api', function (Request $request) {
            return $this->limit(120, 'api', $request);
        });

        RateLimiter::for('mobile-api', function (Request $request) {
            return $this->limit(90, 'mobile-api', $request);
        });

        RateLimiter::for('auth', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(fn () => $this->rateLimitResponse('auth', $request));
        });

        RateLimiter::for('playlists', function (Request $request) {
            return $this->limit(3, 'playlists', $request);
        });

        RateLimiter::for('streams', function (Request $request) {
            return $this->limit(20, 'streams', $request);
        });

        RateLimiter::for('registration', fn (Request $request) => $this->limit(3, 'registration', $request));
        RateLimiter::for('password-reset', fn (Request $request) => $this->limit(3, 'password-reset', $request));
        RateLimiter::for('search', fn (Request $request) => $this->limit(30, 'search', $request));
        RateLimiter::for('channel-catalog', fn (Request $request) => $this->limit(45, 'channel-catalog', $request));

        View::composer('*', function ($view): void {
            $view->with('appSettings', $this->resolveSharedSettings());
        });
    }

    private function limit(int $perMinute, string $name, Request $request): Limit
    {
        return Limit::perMinute($perMinute)
            ->by($request->user()?->id ?: $request->ip())
            ->response(fn () => $this->rateLimitResponse($name, $request));
    }

    private function rateLimitResponse(string $name, Request $request)
    {
        Log::warning('security.rate_limit_hit', [
            'limiter' => $name,
            'path' => $request->path(),
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return response()->json([
            'message' => 'Too many requests. Please wait a moment and try again.',
        ], 429);
    }

    private function shouldForceHttps(): bool
    {
        if ((bool) config('rifimedia.force_https')) {
            return true;
        }

        if ($this->app->runningInConsole()) {
            return false;
        }

        $request = request();
        $forwardedProto = strtolower((string) $request->headers->get('x-forwarded-proto'));
        $host = strtolower($request->getHost());

        return $request->isSecure()
            || str_contains($forwardedProto, 'https')
            || str_ends_with($host, 'rifimedia.com');
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

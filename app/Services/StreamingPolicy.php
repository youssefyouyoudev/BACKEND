<?php

namespace App\Services;

use App\Support\StreamUrl;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class StreamingPolicy
{
    public function assertPlaylistUrlAllowed(string $url): void
    {
        $this->assertAllowed($url, 'playlist', (array) $this->config('streaming.allowed_playlist_domains', []));
    }

    public function assertStreamUrlAllowed(string $url): void
    {
        $this->assertAllowed($url, 'stream', (array) $this->config('streaming.allowed_stream_domains', []));
    }

    public function allowsStreamUrl(?string $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        try {
            return $this->rejectionReason($url, (array) $this->config('streaming.allowed_stream_domains', [])) === null;
        } catch (ValidationException) {
            return false;
        }
    }

    private function assertAllowed(string $url, string $context, array $allowedDomains): void
    {
        $reason = $this->rejectionReason($url, $allowedDomains);

        if ($reason === null) {
            return;
        }

        Log::warning('streaming.url_blocked', [
            'context' => $context,
            'reason' => $reason,
            'url' => StreamUrl::masked($url),
        ]);

        throw ValidationException::withMessages([
            $context === 'playlist' ? 'source_url' : 'stream_url' => [
                'This external URL is not on the approved legal streaming domain allowlist.',
            ],
        ]);
    }

    private function rejectionReason(string $url, array $allowedDomains): ?string
    {
        if (! (bool) $this->config('streaming.enable_external_streams', false)) {
            return 'external_streams_disabled';
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'invalid_url';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return 'unsupported_scheme';
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'embedded_credentials';
        }

        if ($this->isLocalHostname($host)) {
            return 'local_hostname';
        }

        if (! $this->matchesAllowlist($host, $allowedDomains)) {
            return 'domain_not_allowed';
        }

        if ((bool) $this->config('streaming.resolve_dns', true)) {
            foreach ($this->resolveIps($host) as $ip) {
                if (! $this->isPublicIp($ip)) {
                    return 'private_or_reserved_ip';
                }
            }
        }

        return null;
    }

    private function matchesAllowlist(string $host, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $allowedDomain) {
            $allowedDomain = strtolower(ltrim(trim((string) $allowedDomain), '*.'));

            if ($allowedDomain !== '' && ($host === $allowedDomain || str_ends_with($host, '.'.$allowedDomain))) {
                return true;
            }
        }

        return false;
    }

    private function isLocalHostname(string $host): bool
    {
        return $host === 'localhost'
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal');
    }

    /**
     * @return list<string>
     */
    private function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $ips = [];

        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }

            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            throw ValidationException::withMessages([
                'source_url' => ['The external host could not be resolved.'],
            ]);
        }

        return array_values(array_unique($ips));
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function config(string $key, mixed $default): mixed
    {
        try {
            return config($key, $default);
        } catch (Throwable) {
            return match ($key) {
                'streaming.enable_external_streams' => $this->environmentBool(
                    'STREAMING_ENABLE_EXTERNAL_STREAMS',
                    (bool) $default
                ),
                'streaming.resolve_dns' => $this->environmentBool('STREAMING_RESOLVE_DNS', (bool) $default),
                'streaming.allowed_stream_domains' => $this->environmentDomains('STREAMING_ALLOWED_DOMAINS'),
                'streaming.allowed_playlist_domains' => $this->environmentDomains(
                    'STREAMING_ALLOWED_PLAYLIST_DOMAINS',
                    'STREAMING_ALLOWED_DOMAINS'
                ),
                default => $default,
            };
        }
    }

    private function environmentBool(string $key, bool $default): bool
    {
        $value = $_ENV[$key] ?? getenv($key);

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @return list<string>
     */
    private function environmentDomains(string $key, ?string $fallbackKey = null): array
    {
        $value = $_ENV[$key] ?? getenv($key);

        if (($value === false || $value === '') && $fallbackKey !== null) {
            $value = $_ENV[$fallbackKey] ?? getenv($fallbackKey);
        }

        return array_values(array_filter(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            explode(',', is_string($value) ? $value : '')
        )));
    }
}

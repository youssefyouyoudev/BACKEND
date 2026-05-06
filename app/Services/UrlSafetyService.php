<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class UrlSafetyService
{
    public function assertSafeForImport(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'source_url' => ['A valid playlist URL is required.'],
            ]);
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw ValidationException::withMessages([
                'source_url' => ['Only public HTTP(S) playlist URLs are allowed.'],
            ]);
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw ValidationException::withMessages([
                'source_url' => ['Embedded credentials are not allowed in playlist URLs.'],
            ]);
        }

        if ($this->isLocalHostname($host)) {
            throw ValidationException::withMessages([
                'source_url' => ['Local or private network hosts are blocked for security reasons.'],
            ]);
        }

        $ips = $this->resolveIps($host);

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw ValidationException::withMessages([
                    'source_url' => ['The provided playlist host resolves to a private or reserved IP address.'],
                ]);
            }
        }
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

        $resolved = gethostbynamel($host) ?: [];

        if ($resolved === [] || $resolved === [$host]) {
            throw ValidationException::withMessages([
                'source_url' => ['The playlist URL host could not be resolved.'],
            ]);
        }

        return array_values(array_unique($resolved));
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}

<?php

namespace App\Support;

class StreamUrl
{
    public static function browserSafe(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return $url;
        }

        $parts = parse_url($url);

        if (
            ! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'http'
            || isset($parts['port'])
        ) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url, 1) ?? $url;
    }

    public static function proxied(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return $url;
        }

        $url = trim($url);

        return route('stream.proxy', [
            'encodedUrl' => self::encodeProxyUrl($url),
            'sig' => self::proxySignature($url),
        ]);
    }

    public static function encodeProxyUrl(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    public static function decodeProxyUrl(string $encodedUrl): ?string
    {
        $decoded = base64_decode(strtr($encodedUrl, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    public static function hasValidProxySignature(string $url, string $signature): bool
    {
        return hash_equals(self::proxySignature($url), $signature);
    }

    public static function isPlaylist(string $url, string $contentType, string $body): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $contentType = strtolower($contentType);

        return str_ends_with($path, '.m3u8')
            || str_contains($contentType, 'mpegurl')
            || str_starts_with(ltrim($body), '#EXTM3U');
    }

    public static function isLikelyPlaylistUrl(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $query = strtolower((string) parse_url($url, PHP_URL_QUERY));

        return str_ends_with($path, '.m3u8')
            || str_ends_with($path, '/m3u8')
            || str_contains($query, 'm3u8');
    }

    public static function isLikelyMpegTsUrl(string $url, ?string $contentType = null): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $contentType = strtolower((string) $contentType);

        return str_ends_with($path, '.ts')
            || str_ends_with($path, '/ts')
            || str_contains($contentType, 'video/mp2t')
            || str_contains($contentType, 'mpegts');
    }

    public static function contentTypeFor(string $url, ?string $contentType = null): string
    {
        $contentType = trim((string) $contentType);

        if ($contentType !== '') {
            return $contentType;
        }

        if (self::isLikelyPlaylistUrl($url)) {
            return 'application/vnd.apple.mpegurl';
        }

        if (self::isLikelyMpegTsUrl($url)) {
            return 'video/mp2t';
        }

        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return str_ends_with($path, '.mp4') ? 'video/mp4' : 'application/octet-stream';
    }

    public static function masked(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return '[invalid-url]';
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $segments = $path === '' ? [] : explode('/', $path);
        $first = $segments[0] ?? '';
        $last = $segments !== [] ? end($segments) : '';
        $maskedPath = $first === '' ? '' : '/'.$first.'/***'.($last && $last !== $first ? '/'.$last : '');

        return $parts['scheme'].'://'.$parts['host'].$port.$maskedPath;
    }

    public static function rewritePlaylist(string $body, string $baseUrl): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];

        return implode("\n", array_map(
            fn (string $line): string => self::rewritePlaylistLine($line, $baseUrl),
            $lines
        ));
    }

    private static function rewritePlaylistLine(string $line, string $baseUrl): string
    {
        $trimmed = trim($line);

        if ($trimmed === '') {
            return $line;
        }

        if (str_starts_with($trimmed, '#')) {
            return (string) preg_replace_callback(
                '/URI="([^"]+)"/',
                fn (array $matches): string => 'URI="'.self::proxied(self::resolve($matches[1], $baseUrl)).'"',
                $line
            );
        }

        return self::proxied(self::resolve($trimmed, $baseUrl)) ?? $line;
    }

    private static function resolve(string $candidate, string $baseUrl): string
    {
        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $candidate) === 1) {
            return $candidate;
        }

        $base = parse_url($baseUrl);

        if ($base === false || ! isset($base['scheme'], $base['host'])) {
            return $candidate;
        }

        if (str_starts_with($candidate, '//')) {
            return $base['scheme'].':'.$candidate;
        }

        $basePath = $base['path'] ?? '/';
        $directory = (string) preg_replace('#/[^/]*$#', '/', $basePath) ?: '/';
        $resolvedPath = str_starts_with($candidate, '/') ? $candidate : $directory.$candidate;
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        return $base['scheme'].'://'.$base['host'].$port.$resolvedPath;
    }

    private static function proxySignature(string $url): string
    {
        return hash_hmac('sha256', $url, (string) config('app.key'));
    }
}

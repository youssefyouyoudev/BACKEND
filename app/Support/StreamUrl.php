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
}

<?php

namespace App\Services;

use Illuminate\Support\Str;

class IptvChannelNormalizer
{
    public function normalize(?string $name): string
    {
        $value = Str::of((string) $name)
            ->replaceMatches('/^\s*(?:\[[A-Z]{2,4}\]|\([A-Z]{2,4}\)|\|[A-Z]{2,4}\|)\s*/iu', '')
            ->replaceMatches('/\b(?:UHD|FHD|FULL[\s._-]*HD|HD\+?|SD|4K|2160P|1080P|720P|HEVC|H\.?265|VIP)\b/iu', ' ')
            ->replaceMatches('/\b(?:BACKUP|SERVER)\s*\d*\b/iu', ' ')
            ->replaceMatches('/\b(?:LOW|HIGH)\b/iu', ' ')
            ->replaceMatches('/[\s._-]+/u', ' ')
            ->squish()
            ->lower()
            ->toString();

        return $value !== '' ? $value : 'channel';
    }

    public function quality(?string $name, ?string $extension = null): string
    {
        $value = mb_strtoupper(trim((string) $name).' '.trim((string) $extension));

        return match (true) {
            preg_match('/\b(?:4K|UHD|2160P)\b/u', $value) === 1 => '4K',
            preg_match('/\b(?:FHD|FULL[\s._-]*HD|1080P)\b/u', $value) === 1 => 'FHD',
            preg_match('/\b(?:HD\+?|720P|HEVC|H\.?265)\b/u', $value) === 1 => 'HD',
            preg_match('/\b(?:SD|480P|LOW)\b/u', $value) === 1 => 'SD',
            default => 'Auto',
        };
    }

    public function streamType(?string $url, ?string $extension = null): string
    {
        $path = mb_strtolower((string) parse_url((string) $url, PHP_URL_PATH));
        $query = mb_strtolower((string) parse_url((string) $url, PHP_URL_QUERY));
        $extension = mb_strtolower((string) $extension);

        return match (true) {
            in_array($extension, ['hls', 'm3u8'], true),
            str_ends_with($path, '.m3u8'),
            str_contains($query, 'output=hls') => 'hls',
            in_array($extension, ['mpegts', 'ts'], true),
            str_ends_with($path, '.ts'),
            str_contains($query, 'output=mpegts') => 'mpegts',
            $extension === 'mp4',
            str_ends_with($path, '.mp4') => 'mp4',
            default => 'auto',
        };
    }
}

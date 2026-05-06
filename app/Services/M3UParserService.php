<?php

namespace App\Services;

use App\Models\Playlist;
use App\Support\StreamUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class M3UParserService
{
    public function __construct(
        private readonly UrlSafetyService $urlSafetyService,
    ) {
    }

    public function parsePlaylist(Playlist $playlist): array
    {
        $source = $this->loadSource($playlist);

        return $this->parseContent($source['content'], $source['base_url']);
    }

    /**
     * @return array{content:string,base_url:?string}
     */
    public function loadSource(Playlist $playlist): array
    {
        $filePath = $playlist->resolved_file_path;

        if ($filePath) {
            if (Storage::disk('playlists')->exists($filePath)) {
                return [
                    'content' => Storage::disk('playlists')->get($filePath),
                    'base_url' => null,
                ];
            }

            if (Storage::disk('local')->exists($filePath)) {
                return [
                    'content' => Storage::disk('local')->get($filePath),
                    'base_url' => null,
                ];
            }

            throw ValidationException::withMessages([
                'playlist' => ['The uploaded playlist file could not be found in storage.'],
            ]);
        }

        if ($playlist->source_url) {
            $this->urlSafetyService->assertSafeForImport($playlist->source_url);

            $response = Http::timeout(60)
                ->connectTimeout(15)
                ->accept('application/x-mpegURL, application/vnd.apple.mpegurl, text/plain, */*')
                ->withOptions([
                    'allow_redirects' => [
                        'max'             => 5,
                        'strict'          => false,
                        'referer'         => true,
                        'track_redirects' => false,
                    ],
                ])
                ->get($playlist->source_url);

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'playlist' => ['The playlist URL could not be fetched (HTTP '.$response->status().').'],
                ]);
            }

            return [
                'content'  => (string) $response->body(),
                'base_url' => $playlist->source_url,
            ];
        }

        throw ValidationException::withMessages([
            'playlist' => ['This playlist does not have a valid URL or uploaded file source.'],
        ]);
    }

    public function parseContent(string $content, ?string $baseUrl = null): array
    {
        // Normalise line endings and strip BOM
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines   = preg_split('/\r\n|\r|\n/', $content) ?: [];

        $entries        = [];
        $seenHashes     = [];
        $groups         = [];
        $currentExtInf  = null;
        $fallbackGroup  = null;
        $playlistTitle  = null;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            // ── Header ──────────────────────────────────────────────────────
            if (str_starts_with($line, '#EXTM3U')) {
                $playlistTitle = $this->sanitizeString($this->extractHeaderTitle($line));
                continue;
            }

            // ── Fallback group directive ─────────────────────────────────────
            if (str_starts_with($line, '#EXTGRP:')) {
                $fallbackGroup = $this->sanitizeString(substr($line, 8));
                continue;
            }

            // ── Channel metadata line ────────────────────────────────────────
            if (str_starts_with($line, '#EXTINF:')) {
                $currentExtInf = $this->parseExtInf($line);

                if (empty($currentExtInf['group_title']) && $fallbackGroup) {
                    $currentExtInf['group_title'] = $fallbackGroup;
                }

                continue;
            }

            // Skip other directives
            if (str_starts_with($line, '#')) {
                continue;
            }

            // Not a URL line without prior #EXTINF — skip
            if ($currentExtInf === null) {
                continue;
            }

            $streamUrl  = StreamUrl::browserSafe($this->resolveUrl($line, $baseUrl));
            $streamHash = sha1(strtolower($streamUrl));

            // Deduplicate
            if (isset($seenHashes[$streamHash])) {
                $currentExtInf = null;
                continue;
            }

            $seenHashes[$streamHash] = true;
            $groupTitle = $this->sanitizeString($currentExtInf['group_title'] ?? null);

            if ($groupTitle) {
                $groups[$groupTitle] = true;
            }

            $entries[] = [
                'tvg_id'      => $this->sanitizeString($currentExtInf['tvg_id'] ?? null),
                'name'        => $this->sanitizeString($currentExtInf['name'] ?? null) ?: 'Untitled Channel',
                'logo'        => $this->resolveOptionalUrl($currentExtInf['logo'] ?? null, $baseUrl),
                'group_title' => $groupTitle,
                'stream_url'  => $streamUrl,
                'stream_type' => $this->detectStreamType($streamUrl),
                'stream_hash' => $streamHash,
                'metadata'    => Arr::only($currentExtInf, ['duration', 'raw_attributes']),
            ];

            $currentExtInf = null;
        }

        if ($entries === []) {
            throw ValidationException::withMessages([
                'playlist' => ['The playlist did not contain any valid channels.'],
            ]);
        }

        return [
            'title'   => $playlistTitle ?: null,
            'entries' => $entries,
            'groups'  => array_keys($groups),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function parseExtInf(string $line): array
    {
        // Duration: can be integer or float (e.g. #EXTINF:-1 or #EXTINF:30.5)
        preg_match('/#EXTINF:\s*(-?[\d.]+)/', $line, $durationMatch);

        $attributes = $this->parseAttributes($line);

        // Channel name is everything after the LAST comma that is not inside quotes.
        // The name section starts after all key="value" pairs end.
        $name = $this->extractChannelName($line, $attributes);

        return [
            'duration'       => isset($durationMatch[1]) ? (float) $durationMatch[1] : null,
            'tvg_id'         => $attributes['tvg-id'] ?? null,
            'name'           => $attributes['tvg-name'] ?? $name,
            'logo'           => $attributes['tvg-logo'] ?? null,
            'group_title'    => $attributes['group-title'] ?? null,
            'raw_attributes' => $attributes,
        ];
    }

    /**
     * Extract the channel display name from the tail of an #EXTINF line.
     *
     * The name sits after the LAST comma, e.g.:
     *   #EXTINF:-1 tvg-id="foo" group-title="Bar, HD",My Channel Name
     *
     * We find the end of the last quoted attribute value, then look for the
     * first comma after that position.
     */
    private function extractChannelName(string $line, array $attributes): ?string
    {
        // Find where the last quoted attribute ends
        $lastQuoteEnd = 0;

        if (preg_match_all('/=(?:"[^"]*"|\'[^\']*\')/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $end = $match[1] + strlen($match[0]);
                if ($end > $lastQuoteEnd) {
                    $lastQuoteEnd = $end;
                }
            }
        }

        // Find the first comma after all quoted attribute values
        $commaPos = strpos($line, ',', $lastQuoteEnd);

        if ($commaPos === false) {
            return null;
        }

        $name = substr($line, $commaPos + 1);

        return $name !== '' ? trim($name) : null;
    }

    /**
     * Parse key="value" or key='value' attribute pairs.
     *
     * @return array<string, string>
     */
    private function parseAttributes(string $line): array
    {
        // Match both double-quoted and single-quoted values
        preg_match_all('/([a-zA-Z0-9\-_]+)=(?:"([^"]*)"|\'([^\']*)\')/', $line, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            // $match[2] = double-quoted value, $match[3] = single-quoted value
            $attributes[strtolower($match[1])] = $match[2] !== '' ? $match[2] : ($match[3] ?? '');
        }

        return $attributes;
    }

    private function resolveUrl(string $candidate, ?string $baseUrl): string
    {
        // Already absolute
        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $candidate) === 1) {
            return $candidate;
        }

        if ($baseUrl === null) {
            return $candidate;
        }

        $base = parse_url($baseUrl);

        if ($base === false || ! isset($base['scheme'], $base['host'])) {
            return $candidate;
        }

        // Protocol-relative URL
        if (str_starts_with($candidate, '//')) {
            return $base['scheme'].':'.$candidate;
        }

        $basePath  = $base['path'] ?? '/';
        $directory = (string) preg_replace('#/[^/]*$#', '/', $basePath) ?: '/';

        $resolvedPath = str_starts_with($candidate, '/')
            ? $candidate
            : $directory.$candidate;

        $port = isset($base['port']) ? ':'.$base['port'] : '';

        return $base['scheme'].'://'.$base['host'].$port.$resolvedPath;
    }

    private function resolveOptionalUrl(?string $value, ?string $baseUrl): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $this->resolveUrl($value, $baseUrl);
    }

    private function detectStreamType(string $streamUrl): string
    {
        $urlPath  = strtolower((string) parse_url($streamUrl, PHP_URL_PATH));
        $query    = strtolower((string) parse_url($streamUrl, PHP_URL_QUERY));

        // HLS variants
        if (
            str_ends_with($urlPath, '.m3u8')
            || str_ends_with($urlPath, '/m3u8')
            || str_contains($query, 'm3u8')
            || str_contains($query, 'type=m3u_plus')
        ) {
            return 'hls';
        }

        // Plain M3U (treat as HLS for player purposes)
        if (str_ends_with($urlPath, '.m3u') || str_ends_with($urlPath, '/m3u')) {
            return 'hls';
        }

        // MPEG-DASH
        if (str_ends_with($urlPath, '.mpd')) {
            return 'dash';
        }

        // Xtream-Codes API patterns  (e.g. /live/user/pass/12345.ts or /live/user/pass/12345)
        if (
            str_contains($urlPath, '/live/')
            || preg_match('#/live/[^/]+/[^/]+/#', $urlPath)
        ) {
            return 'hls';
        }

        if (str_contains($urlPath, '/movie/')) {
            return 'mp4';
        }

        if (str_contains($urlPath, '/series/')) {
            return 'mp4';
        }

        // File-extension based
        return match (true) {
            str_ends_with($urlPath, '.mp4'),
            str_ends_with($urlPath, '.mkv'),
            str_ends_with($urlPath, '.avi') => 'mp4',
            str_ends_with($urlPath, '.ts')  => 'mpegts',
            default                          => 'stream',
        };
    }

    private function extractHeaderTitle(string $line): ?string
    {
        $attributes = $this->parseAttributes($line);

        return $attributes['playlist-name']
            ?? $attributes['x-tvg-name']
            ?? null;
    }

    private function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Decode HTML entities, strip tags, collapse whitespace
        $sanitized = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $sanitized = (string) preg_replace('/\s+/u', ' ', trim($sanitized));

        return $sanitized === '' ? null : $sanitized;
    }
}

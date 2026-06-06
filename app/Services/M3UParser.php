<?php

namespace App\Services;

use Illuminate\Support\Arr;

class M3UParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $content): array
    {
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $items = [];
        $current = null;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '' || str_starts_with($line, '#EXTM3U')) {
                continue;
            }

            if (str_starts_with($line, '#EXTINF:')) {
                $current = $this->parseExtInf($line);
                continue;
            }

            if (str_starts_with($line, '#') || $current === null) {
                continue;
            }

            if (! $this->isHttpUrl($line)) {
                $current = null;
                continue;
            }

            $streamUrl = trim($line);
            $items[] = array_merge($current, [
                'stream_url' => $streamUrl,
                'type' => $this->detectType($streamUrl),
                'extension' => $this->detectExtension($streamUrl),
            ]);

            $current = null;
        }

        return $items;
    }

    private function parseExtInf(string $line): array
    {
        $attributes = $this->parseAttributes($line);
        $name = $attributes['tvg-name'] ?? $this->extractName($line) ?? 'Untitled';
        $groupTitle = $attributes['group-title'] ?? null;

        return [
            'name' => $this->clean($name),
            'tvg_id' => $this->clean($attributes['tvg-id'] ?? null),
            'tvg_name' => $this->clean($attributes['tvg-name'] ?? null),
            'tvg_logo' => $this->clean($attributes['tvg-logo'] ?? null),
            'logo' => $this->clean($attributes['tvg-logo'] ?? null),
            'group_title' => $this->clean($groupTitle),
            'is_adult' => $this->isAdult($name, $groupTitle),
            'raw_data' => [
                'attributes' => Arr::except($attributes, []),
                'extinf' => $line,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseAttributes(string $line): array
    {
        preg_match_all('/([a-zA-Z0-9\-_]+)=(?:"([^"]*)"|\'([^\']*)\')/', $line, $matches, PREG_SET_ORDER);
        $attributes = [];

        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = $match[2] !== '' ? $match[2] : ($match[3] ?? '');
        }

        return $attributes;
    }

    private function extractName(string $line): ?string
    {
        $lastQuoteEnd = 0;

        if (preg_match_all('/=(?:"[^"]*"|\'[^\']*\')/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $lastQuoteEnd = max($lastQuoteEnd, $match[1] + strlen($match[0]));
            }
        }

        $commaPosition = strpos($line, ',', $lastQuoteEnd);

        return $commaPosition === false ? null : trim(substr($line, $commaPosition + 1));
    }

    private function detectType(string $streamUrl): string
    {
        $path = strtolower((string) parse_url($streamUrl, PHP_URL_PATH));

        if (str_contains($path, '/series/')) {
            return 'series';
        }

        if (str_contains($path, '/movie/') || preg_match('/\.(mp4|mkv|avi)$/', $path) === 1) {
            return 'movie';
        }

        return 'live';
    }

    private function detectExtension(string $streamUrl): ?string
    {
        $path = strtolower((string) parse_url($streamUrl, PHP_URL_PATH));

        if (preg_match('/\.([a-z0-9]+)$/', $path, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function isHttpUrl(string $value): bool
    {
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $clean === '' ? null : $clean;
    }

    private function isAdult(?string ...$values): bool
    {
        $text = mb_strtolower(implode(' ', array_filter($values)));

        return str_contains($text, 'adult')
            || str_contains($text, 'xxx')
            || str_contains($text, '18+')
            || str_contains($text, 'porn');
    }
}

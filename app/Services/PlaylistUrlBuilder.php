<?php

namespace App\Services;

use App\Models\Playlist;

class PlaylistUrlBuilder
{
    public function buildFromXtream(string $serverUrl, string $username, string $password, string $output = 'mpegts'): string
    {
        return $this->buildUrl($serverUrl, 'get.php', [
            'username' => $username,
            'password' => $password,
            'type' => 'm3u_plus',
            'output' => $this->normalizeOutput($output),
        ]);
    }

    public function buildXtreamApiUrl(string $serverUrl, string $username, string $password, ?string $action = null): string
    {
        $query = [
            'username' => $username,
            'password' => $password,
        ];

        if ($action) {
            $query['action'] = $action;
        }

        return $this->buildUrl($serverUrl, 'player_api.php', $query);
    }

    public function normalizeProviderUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);

        if (isset($query['user']) && ! isset($query['username'])) {
            $query['username'] = $query['user'];
            unset($query['user']);
        }

        if (isset($query['pass']) && ! isset($query['password'])) {
            $query['password'] = $query['pass'];
            unset($query['pass']);
        }

        if (($query['t'] ?? null) === 'm3uplus' && ! isset($query['type'])) {
            $query['type'] = 'm3u_plus';
            unset($query['t']);
        }

        if (isset($query['o']) && ! isset($query['output'])) {
            $query['output'] = $query['o'];
            unset($query['o']);
        }

        return $this->rebuildParsedUrl($parts, $query);
    }

    public function maskSensitiveUrl(?string $url): ?string
    {
        return Playlist::maskSensitiveUrl($url);
    }

    private function buildUrl(string $serverUrl, string $path, array $query): string
    {
        $base = rtrim($serverUrl, '/');

        return $base.'/'.$path.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function normalizeOutput(string $output): string
    {
        return in_array($output, ['mpegts', 'hls'], true) ? $output : 'mpegts';
    }

    private function rebuildParsedUrl(array $parts, array $query): string
    {
        $scheme = $parts['scheme'].'://';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $scheme.$host.$port.$path.($queryString !== '' ? '?'.$queryString : '').$fragment;
    }
}

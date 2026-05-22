<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChannelMatcherService
{
    private const CHANNEL_CACHE_KEY = 'channel-matcher:public-channels';
    private const SIMILARITY_THRESHOLD = 85.0;

    public function normalizeChannelName(?string $name): string
    {
        $name = Str::of((string) $name)
            ->ascii()
            ->lower()
            ->replace(['+', '&'], ' plus ')
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();

        if ($name === '') {
            return '';
        }

        $qualityTokens = ['hd', 'fhd', 'uhd', '4k'];
        $wordMap = [
            'channel' => '',
            'tv' => '',
            'television' => '',
            'sports' => 'sport',
            'sporting' => 'sport',
            'bein' => 'bein',
            'beinsports' => 'bein sport',
            'canalplus' => 'canal plus',
        ];

        $seen = [];
        $regular = [];
        $qualities = [];

        foreach (explode(' ', $name) as $token) {
            $token = $wordMap[$token] ?? $token;

            if ($token === '') {
                continue;
            }

            if ($token === 'ultra') {
                $token = 'uhd';
            }

            if ($token === 'fullhd') {
                $token = 'fhd';
            }

            if (in_array($token, $qualityTokens, true)) {
                $qualities[$token] = $token;
                continue;
            }

            if (isset($seen[$token])) {
                continue;
            }

            $seen[$token] = true;
            $regular[] = $token;
        }

        $tokens = array_merge($regular, array_values($qualities));

        return trim(implode(' ', $tokens));
    }

    public function findMatchingChannel(string $tvChannelName, ?string $country = null): ?array
    {
        $match = $this->matchChannel($tvChannelName, $country);
        $channel = $match['channel'];

        if (! $channel instanceof Channel) {
            return null;
        }

        return [
            'id' => $channel->id,
            'name' => $channel->clean_display_name,
            'slug' => $channel->slug,
            'logo' => $channel->logo,
            'watch_url' => $this->buildWatchUrl($channel),
            'confidence' => $match['confidence'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tvChannels
     * @return array<int, array<string, mixed>>
     */
    public function enrichTvChannelsWithPlaylistLinks(array $tvChannels): array
    {
        return collect($tvChannels)
            ->map(function (array $tvChannel): array {
                $originalName = $this->sanitizeChannelName((string) ($tvChannel['channel'] ?? $tvChannel['name'] ?? ''));
                $country = $this->sanitizeChannelName((string) ($tvChannel['country'] ?? '')) ?: null;
                $match = $this->matchChannel($originalName, $country);
                $channel = $match['channel'];

                return [
                    'channel' => $originalName,
                    'name' => $originalName,
                    'country' => $country,
                    'available' => $channel instanceof Channel,
                    'matched_channel_id' => $channel?->id,
                    'matched_channel_name' => $channel?->clean_display_name,
                    'matched_channel_slug' => $channel?->slug,
                    'logo' => $channel?->logo,
                    'watch_url' => $channel ? $this->buildWatchUrl($channel) : null,
                    'confidence' => $match['confidence'],
                ];
            })
            ->filter(fn (array $channel): bool => $channel['channel'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{input: string, normalized: string, matched: bool, confidence: float, channel: ?array{id: int, name: string, slug: ?string}}
     */
    public function debugMatch(string $tvChannelName, ?string $country = null): array
    {
        $match = $this->matchChannel($tvChannelName, $country);
        $channel = $match['channel'];

        return [
            'input' => $tvChannelName,
            'normalized' => $this->normalizeChannelName($tvChannelName),
            'matched' => $channel instanceof Channel,
            'confidence' => $match['confidence'],
            'channel' => $channel ? [
                'id' => $channel->id,
                'name' => $channel->clean_display_name,
                'slug' => $channel->slug,
                'watch_url' => $this->buildWatchUrl($channel),
            ] : null,
        ];
    }

    public function similarityScore(string $a, string $b): int
    {
        return (int) round($this->similarity($this->normalizeChannelName($a), $this->normalizeChannelName($b)));
    }

    public function buildWatchUrl(Channel $channel): string
    {
        return '/watch/'.($channel->slug ?: $channel->id);
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CHANNEL_CACHE_KEY);
    }

    /**
     * @return array{channel: ?Channel, confidence: float}
     */
    private function matchChannel(string $tvChannelName, ?string $country = null): array
    {
        $normalizedInput = $this->normalizeChannelName($tvChannelName);
        $manualAliasInput = Str::of($tvChannelName)
            ->ascii()
            ->lower()
            ->replace(['+', '&'], ' plus ')
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();

        if ($normalizedInput === '') {
            return ['channel' => null, 'confidence' => 0.0];
        }

        $overrideSlug = config("channel_aliases.{$manualAliasInput}") ?: config("channel_aliases.{$normalizedInput}");

        if (is_string($overrideSlug) && $overrideSlug !== '') {
            $override = $this->channels()->firstWhere('slug', $overrideSlug);

            if ($override instanceof Channel) {
                return ['channel' => $override, 'confidence' => 100.0];
            }
        }

        $channels = $this->channels();

        $exactName = $channels->first(fn (Channel $channel): bool => $this->normalizeChannelName($channel->name) === $normalizedInput);

        if ($exactName instanceof Channel) {
            return ['channel' => $exactName, 'confidence' => 100.0];
        }

        $exactAlias = $channels->first(function (Channel $channel) use ($normalizedInput): bool {
            return collect($channel->aliases ?? [])
                ->filter(fn ($alias): bool => is_string($alias) && trim($alias) !== '')
                ->contains(fn (string $alias): bool => $this->normalizeChannelName($alias) === $normalizedInput);
        });

        if ($exactAlias instanceof Channel) {
            return ['channel' => $exactAlias, 'confidence' => 100.0];
        }

        $normalizedCountry = $country ? $this->normalizeChannelName($country) : null;

        if ($normalizedCountry) {
            $countryName = $channels->first(fn (Channel $channel): bool => $this->normalizeChannelName((string) $channel->country) === $normalizedCountry
                && $this->normalizeChannelName($channel->name) === $normalizedInput);

            if ($countryName instanceof Channel) {
                return ['channel' => $countryName, 'confidence' => 100.0];
            }
        }

        $bestChannel = null;
        $bestScore = 0.0;

        foreach ($channels as $channel) {
            $candidates = array_filter([
                $channel->name,
                $channel->clean_display_name,
                ...($channel->aliases ?? []),
            ], fn ($candidate): bool => is_string($candidate) && trim($candidate) !== '');

            foreach ($candidates as $candidate) {
                $score = $this->similarity($normalizedInput, $this->normalizeChannelName($candidate));

                if ($normalizedCountry && $channel->country && $this->normalizeChannelName((string) $channel->country) === $normalizedCountry) {
                    $score += 2.5;
                }

                if ($score > $bestScore) {
                    $bestScore = min(100.0, $score);
                    $bestChannel = $channel;
                }
            }
        }

        if ($bestChannel instanceof Channel && $bestScore >= self::SIMILARITY_THRESHOLD) {
            return ['channel' => $bestChannel, 'confidence' => round($bestScore, 2)];
        }

        return ['channel' => null, 'confidence' => round($bestScore, 2)];
    }

    /**
     * @return Collection<int, Channel>
     */
    private function channels(): Collection
    {
        return Cache::remember(self::CHANNEL_CACHE_KEY, now()->addMinutes(10), fn () => Channel::query()
            ->where('is_active', true)
            ->canonical()
            ->whereHas('playlist', fn (Builder $query) => $query
                ->where('is_public', true)
                ->whereNotNull('approved_at'))
            ->get());
    }

    private function similarity(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 100.0;
        }

        similar_text($left, $right, $percent);

        return (float) $percent;
    }

    private function sanitizeChannelName(string $name): string
    {
        return Str::of($name)
            ->squish()
            ->limit(160, '')
            ->toString();
    }

}

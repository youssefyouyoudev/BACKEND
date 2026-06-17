<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\IptvItem;
use App\Models\WorldCupMatch;
use App\Support\StreamUrl;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class MatchWatchController extends Controller
{
    public function show(WorldCupMatch $worldCupMatch): View
    {
        $worldCupMatch->load([
            'selectedChannel.playlist',
            'selectedIptvItem.playlist',
            'iptvItems.playlist',
        ]);

        $now = CarbonImmutable::now(WorldCupMatch::MOROCCO_TIMEZONE);
        $watchItems = $worldCupMatch->availableWatchItems($now);
        $manualWatchUrl = $this->manualWatchUrl($worldCupMatch);
        $sources = $this->matchPlayerSources($worldCupMatch, $now);

        return view('matches.watch', [
            'match' => $worldCupMatch,
            'status' => $worldCupMatch->watchStatus($now),
            'watchItems' => $watchItems,
            'sources' => $sources,
            'playerType' => $worldCupMatch->player_type ?: 'iframe',
            'manualWatchUrl' => $manualWatchUrl,
            'upcomingMatches' => $this->upcomingMatches($worldCupMatch, $now),
            'schema' => $this->schema($worldCupMatch),
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    public function embed(Request $request, WorldCupMatch $worldCupMatch): View
    {
        abort_unless($request->hasValidRelativeSignature(), Response::HTTP_FORBIDDEN);

        $worldCupMatch->load([
            'selectedChannel.playlist',
            'selectedIptvItem.playlist',
            'iptvItems.playlist',
        ]);

        abort_unless($worldCupMatch->isWatchOpen(), Response::HTTP_GONE);

        $source = $this->resolveEmbedSource($worldCupMatch, $request);

        abort_unless($source !== null, Response::HTTP_NOT_FOUND);

        return view('matches.embed', [
            'match' => $worldCupMatch,
            'source' => $source,
        ]);
    }

    public function watchLink(
        Request $request,
        WorldCupMatch $worldCupMatch,
        IptvItem $item
    ): RedirectResponse {
        abort_unless($request->hasValidRelativeSignature(), Response::HTTP_FORBIDDEN);

        $worldCupMatch->load(['selectedIptvItem.playlist', 'iptvItems.playlist']);

        abort_unless($worldCupMatch->isWatchOpen(), Response::HTTP_GONE);
        abort_unless(
            $worldCupMatch->availableWatchItems()->contains(
                fn (IptvItem $availableItem): bool => $availableItem->is($item)
            ),
            Response::HTTP_NOT_FOUND
        );

        return redirect()->to(StreamUrl::matchIptvItemBridge(
            $item->getKey(),
            $worldCupMatch->getKey(),
            $worldCupMatch->watch_expires_at,
        ));
    }

    public function manualWatchLink(Request $request, WorldCupMatch $worldCupMatch): RedirectResponse
    {
        abort_unless($request->hasValidRelativeSignature(), Response::HTTP_FORBIDDEN);
        abort_unless($worldCupMatch->isWatchOpen(), Response::HTTP_GONE);
        abort_unless(
            $worldCupMatch->is_live_link_enabled
            && $worldCupMatch->use_manual_live_url
            && filled($worldCupMatch->live_url_manual),
            Response::HTTP_NOT_FOUND
        );

        return redirect()->away($worldCupMatch->live_url_manual);
    }

    public function watchChannel(
        Request $request,
        WorldCupMatch $worldCupMatch,
        Channel $channel
    ): RedirectResponse {
        abort_unless($request->hasValidRelativeSignature(), Response::HTTP_FORBIDDEN);

        $worldCupMatch->load('selectedChannel.playlist');

        abort_unless($worldCupMatch->isWatchOpen(), Response::HTTP_GONE);
        abort_unless(
            $worldCupMatch->selected_channel_id === $channel->getKey()
            && $this->hasPlayableSelectedChannel($worldCupMatch),
            Response::HTTP_NOT_FOUND
        );

        return redirect()->to(StreamUrl::matchChannelBridge(
            $channel->getKey(),
            $worldCupMatch->getKey(),
            $worldCupMatch->watch_expires_at,
        ));
    }

    private function matchPlayerSources(WorldCupMatch $match, CarbonImmutable $now)
    {
        if (! $match->is_live_link_enabled || ! $match->isWatchOpen($now)) {
            return collect();
        }

        if ($match->use_manual_live_url && filled($match->live_url_manual)) {
            return collect([$this->manualPlayerSource($match)]);
        }

        $items = $match->availableWatchItems($now);

        if ($items->isNotEmpty()) {
            return $items
                ->map(fn (IptvItem $item, int $index): array => $this->iptvPlayerSource($match, $item, $index))
                ->values();
        }

        if ($this->hasPlayableSelectedChannel($match)) {
            return collect([$this->channelPlayerSource($match)]);
        }

        return collect();
    }

    private function manualPlayerSource(WorldCupMatch $match): array
    {
        $playbackType = $this->streamTypeForUrl($match->live_url_manual, $match->player_type);

        return [
            'id' => 'manual-'.$match->getKey(),
            'source' => 'manual',
            'channel' => $match->channel_name_manual ?: $match->broadcaster ?: __('Manual source'),
            'title' => $match->channel_name_manual ?: "{$match->home_team} vs {$match->away_team}",
            'label' => __('Server 1'),
            'quality' => 'Auto',
            'language' => null,
            'commentator' => $match->commentator,
            'type' => $playbackType,
            'playback_type' => $playbackType,
            'url' => $this->playbackUrl($match->live_url_manual, $match->player_type),
            'external_url' => $match->live_url_manual,
            'recommended' => true,
            'health_status' => 'unknown',
            'embed_url' => $this->embedUrl($match, ['source' => 'manual']),
        ];
    }

    private function iptvPlayerSource(WorldCupMatch $match, IptvItem $item, int $index): array
    {
        $playbackType = $this->streamTypeForUrl($item->stream_url, $item->pivot?->stream_type ?: $item->extension);

        return [
            'id' => $item->getKey(),
            'source' => 'item-'.$item->getKey(),
            'channel' => $item->pivot?->channel_name ?: $item->name,
            'title' => $item->pivot?->stream_title ?: $item->name,
            'label' => $item->pivot?->server_label ?: __('Server :number', ['number' => $index + 1]),
            'quality' => $item->pivot?->quality ?: $item->qualityLabel(),
            'language' => $item->pivot?->language,
            'commentator' => $item->pivot?->commentator ?: $match->commentator,
            'type' => $playbackType,
            'playback_type' => $playbackType,
            'url' => StreamUrl::matchIptvItemBridge(
                $item->getKey(),
                $match->getKey(),
                $match->watch_expires_at,
            ),
            'external_url' => null,
            'recommended' => (bool) ($item->pivot?->is_recommended ?? false),
            'health_status' => $item->pivot?->health_status ?: 'unknown',
            'embed_url' => $this->embedUrl($match, ['source' => 'item', 'item' => $item->getKey()]),
        ];
    }

    private function channelPlayerSource(WorldCupMatch $match): array
    {
        $playbackType = $match->selectedChannel->stream_type ?: 'stream';

        return [
            'id' => 'channel-'.$match->selectedChannel->getKey(),
            'source' => 'channel-'.$match->selectedChannel->getKey(),
            'channel' => $match->selectedChannel->clean_display_name,
            'title' => $match->selectedChannel->clean_display_name,
            'label' => __('Server 1'),
            'quality' => $match->selectedChannel->quality_label ?: 'Auto',
            'language' => null,
            'commentator' => $match->commentator,
            'type' => $playbackType,
            'playback_type' => $playbackType,
            'url' => StreamUrl::matchChannelBridge(
                $match->selectedChannel->getKey(),
                $match->getKey(),
                $match->watch_expires_at,
            ),
            'external_url' => null,
            'recommended' => true,
            'health_status' => 'unknown',
            'embed_url' => $this->embedUrl($match, ['source' => 'channel', 'channel' => $match->selectedChannel->getKey()]),
        ];
    }

    private function embedUrl(WorldCupMatch $match, array $parameters): string
    {
        return url(URL::temporarySignedRoute(
            'matches.embed',
            $match->watch_expires_at,
            ['worldCupMatch' => $match, ...$parameters],
            absolute: false,
        ));
    }

    private function resolveEmbedSource(WorldCupMatch $match, Request $request): ?array
    {
        if ($request->string('source')->toString() === 'manual') {
            if (
                ! $match->is_live_link_enabled
                || ! $match->use_manual_live_url
                || blank($match->live_url_manual)
            ) {
                return null;
            }

            return [
                ...$this->manualPlayerSource($match),
                'type' => $this->streamTypeForUrl($match->live_url_manual, $match->player_type),
                'url' => $this->playbackUrl($match->live_url_manual, $match->player_type),
            ];
        }

        if ($request->string('source')->toString() === 'item') {
            $itemId = $request->integer('item');
            $item = $match->availableWatchItems()
                ->first(fn (IptvItem $availableItem): bool => $availableItem->getKey() === $itemId);

            if (! $item) {
                return null;
            }

            $index = $match->availableWatchItems()->search(fn (IptvItem $availableItem): bool => $availableItem->is($item));

            return [
                ...$this->iptvPlayerSource($match, $item, is_int($index) ? $index : 0),
                'type' => $this->streamTypeForUrl($item->stream_url, $item->pivot?->stream_type ?: $item->extension),
                'url' => StreamUrl::matchIptvItemBridge(
                    $item->getKey(),
                    $match->getKey(),
                    $match->watch_expires_at,
                ),
            ];
        }

        if ($request->string('source')->toString() === 'channel' && $this->hasPlayableSelectedChannel($match)) {
            $channel = $match->selectedChannel;

            if (! $channel || $channel->getKey() !== $request->integer('channel')) {
                return null;
            }

            return [
                ...$this->channelPlayerSource($match),
                'type' => $channel->stream_type ?: 'stream',
                'url' => StreamUrl::matchChannelBridge(
                    $channel->getKey(),
                    $match->getKey(),
                    $match->watch_expires_at,
                ),
            ];
        }

        return null;
    }

    private function playbackUrl(?string $url, ?string $playerType = null): ?string
    {
        if (blank($url)) {
            return null;
        }

        if ($playerType === 'external_embed' || $this->streamTypeForUrl($url, $playerType) === 'iframe') {
            return $url;
        }

        return StreamUrl::signedBridge($url, 60);
    }

    private function manualWatchUrl(WorldCupMatch $match): ?string
    {
        if (
            ! $match->isWatchOpen()
            || ! $match->is_live_link_enabled
            || ! $match->use_manual_live_url
            || blank($match->live_url_manual)
        ) {
            return null;
        }

        return url(URL::temporarySignedRoute(
            'matches.watch-link.manual',
            $match->watch_expires_at,
            ['worldCupMatch' => $match],
            absolute: false,
        ));
    }

    private function streamTypeForUrl(?string $url, ?string $type = null): string
    {
        $type = mb_strtolower((string) $type);

        if (in_array($type, ['iframe', 'external_embed'], true)) {
            return 'iframe';
        }

        if (in_array($type, ['hls', 'm3u', 'm3u8', 'mpegts', 'ts', 'mp4'], true)) {
            return $type === 'ts' ? 'mpegts' : $type;
        }

        $path = mb_strtolower((string) parse_url((string) $url, PHP_URL_PATH));

        return match (true) {
            str_ends_with($path, '.m3u8') => 'hls',
            str_ends_with($path, '.ts') => 'mpegts',
            str_ends_with($path, '.mpegts') => 'mpegts',
            str_ends_with($path, '.mp4') => 'mp4',
            default => 'iframe',
        };
    }

    private function upcomingMatches(WorldCupMatch $match, CarbonImmutable $now)
    {
        return WorldCupMatch::query()
            ->publicVisible()
            ->whereKeyNot($match->getKey())
            ->where('kickoff_at', '>', $now->utc())
            ->with(['selectedIptvItem.playlist', 'iptvItems.playlist'])
            ->orderBy('kickoff_at')
            ->limit(6)
            ->get();
    }

    private function hasPlayableSelectedChannel(WorldCupMatch $match): bool
    {
        return $match->is_live_link_enabled
            && $match->isWatchOpen()
            && $match->selectedChannel?->is_active
            && filled($match->selectedChannel?->stream_url)
            && $match->selectedChannel?->playlist?->is_public
            && filled($match->selectedChannel?->playlist?->approved_at);
    }

    private function schema(WorldCupMatch $match): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => "{$match->home_team} vs {$match->away_team}",
            'startDate' => $match->kickoff_at_morocco?->toIso8601String(),
            'endDate' => $match->expected_ends_at?->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'sport' => 'Football',
            'url' => route('matches.watch', $match),
            'location' => $match->venue ? [
                '@type' => 'Place',
                'name' => collect([$match->venue, $match->city])->filter()->implode(', '),
            ] : null,
            'competitor' => [
                ['@type' => 'SportsTeam', 'name' => $match->home_team],
                ['@type' => 'SportsTeam', 'name' => $match->away_team],
            ],
        ];
    }
}

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
        $sources = $watchItems->map(fn (IptvItem $item, int $index): array => [
            'id' => $item->getKey(),
            'channel' => $item->pivot?->channel_name ?: $item->name,
            'title' => $item->pivot?->stream_title ?: $item->name,
            'label' => $item->pivot?->server_label ?: __('Server :number', ['number' => $index + 1]),
            'quality' => $item->pivot?->quality ?: $item->qualityLabel(),
            'language' => $item->pivot?->language,
            'commentator' => $item->pivot?->commentator ?: $worldCupMatch->commentator,
            'type' => $item->pivot?->stream_type ?: $item->extension ?: 'stream',
            'recommended' => (bool) ($item->pivot?->is_recommended ?? false),
            'health_status' => $item->pivot?->health_status ?: 'unknown',
            'url' => URL::temporarySignedRoute(
                'watch-links.play',
                $worldCupMatch->watch_expires_at,
                ['worldCupMatch' => $worldCupMatch, 'item' => $item],
                absolute: false,
            ),
        ])->values();

        if ($sources->isEmpty() && $this->hasPlayableSelectedChannel($worldCupMatch)) {
            $sources->push([
                'id' => 'channel-'.$worldCupMatch->selectedChannel->getKey(),
                'channel' => $worldCupMatch->selectedChannel->clean_display_name,
                'title' => $worldCupMatch->selectedChannel->clean_display_name,
                'label' => __('Server 1'),
                'quality' => $worldCupMatch->selectedChannel->quality_label ?: 'Auto',
                'language' => null,
                'commentator' => $worldCupMatch->commentator,
                'type' => $worldCupMatch->selectedChannel->stream_type ?: 'stream',
                'recommended' => true,
                'health_status' => 'unknown',
                'url' => URL::temporarySignedRoute(
                    'matches.watch-channel',
                    $worldCupMatch->watch_expires_at,
                    [
                        'worldCupMatch' => $worldCupMatch,
                        'channel' => $worldCupMatch->selectedChannel,
                    ],
                    absolute: false,
                ),
            ]);
        }

        return view('matches.watch', [
            'match' => $worldCupMatch,
            'status' => $worldCupMatch->watchStatus($now),
            'watchItems' => $watchItems,
            'sources' => $sources,
            'manualWatchUrl' => $this->manualWatchUrl($worldCupMatch),
            'upcomingMatches' => $this->upcomingMatches($worldCupMatch, $now),
            'schema' => $this->schema($worldCupMatch),
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
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

        return URL::temporarySignedRoute(
            'matches.watch-link.manual',
            $match->watch_expires_at,
            ['worldCupMatch' => $match],
            absolute: false,
        );
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

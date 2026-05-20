<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\StreamService;
use App\Support\StreamUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ChannelController extends Controller
{
    public function show(Channel $channel, StreamService $streamService): View
    {
        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            404
        );

        $relations = ['playlist', 'category', 'currentProgram', 'programs' => fn ($query) => $query->where('end_time', '>', now())->orderBy('start_time')->limit(8)];

        if (Schema::hasTable('channel_streams')) {
            $relations['streams'] = fn ($query) => $query->where('is_active', true)->orderBy('priority');
        }

        $channel->load($relations);

        $channelList = Channel::query()
            ->where('is_active', true)
            ->canonical()
            ->whereHas('playlist', fn (Builder $query) => $query->where('is_public', true)->whereNotNull('approved_at'))
            ->with([
                'category',
                'currentProgram',
                'playlist',
                'streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority'),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(140)
            ->get()
            ->map(fn (Channel $item) => $this->serializeWatchChannel($item))
            ->values();

        return view('public.channel', [
            'channel' => $channel,
            'activeChannel' => $this->serializeWatchChannel($channel, $streamService),
            'channelList' => $channelList,
            'sources' => $streamService->sourcesFor($channel),
            'programs' => $channel->programs,
        ]);
    }

    private function serializeWatchChannel(Channel $channel, ?StreamService $streamService = null): array
    {
        $source = $channel->active_stream_sources->first();

        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'stream_url' => $source['url'] ?? StreamUrl::proxied($channel->stream_url),
            'stream_type' => $source['type'] ?? $channel->stream_type ?? 'stream',
            'sources' => $streamService ? $streamService->sourcesFor($channel) : $channel->active_stream_sources->values()->all(),
            'category' => $channel->category?->name ?? $channel->group_title ?: 'General',
            'program' => $channel->currentProgram ? [
                'title' => $channel->currentProgram->title,
                'start_time' => $channel->currentProgram->start_time?->format('H:i'),
                'end_time' => $channel->currentProgram->end_time?->format('H:i'),
            ] : null,
            'description' => ($channel->group_title ?: 'Live TV').' stream from '.($channel->playlist?->name ?? 'approved playlist').'.',
        ];
    }
}

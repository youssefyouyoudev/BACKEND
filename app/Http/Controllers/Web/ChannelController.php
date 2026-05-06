<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Support\StreamUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ChannelController extends Controller
{
    public function show(Channel $channel): View
    {
        abort_unless(
            $channel->is_active
            && $channel->playlist()->where('is_public', true)->whereNotNull('approved_at')->exists(),
            404
        );

        $relations = ['playlist'];

        if (Schema::hasTable('channel_streams')) {
            $relations['streams'] = fn ($query) => $query->where('is_active', true)->orderBy('priority');
        }

        $channel->load($relations);

        $channelList = Channel::query()
            ->where('is_active', true)
            ->whereHas('playlist', fn (Builder $query) => $query->where('is_public', true)->whereNotNull('approved_at'))
            ->with([
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
            'activeChannel' => $this->serializeWatchChannel($channel),
            'channelList' => $channelList,
        ]);
    }

    private function serializeWatchChannel(Channel $channel): array
    {
        $source = $channel->active_stream_sources->first();

        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'logo' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'stream_url' => $source['url'] ?? StreamUrl::proxied($channel->stream_url),
            'stream_type' => $source['type'] ?? $channel->stream_type ?? 'hls',
            'category' => $channel->group_title ?: 'General',
            'description' => ($channel->group_title ?: 'Live TV').' stream from '.($channel->playlist?->name ?? 'approved playlist').'.',
        ];
    }
}

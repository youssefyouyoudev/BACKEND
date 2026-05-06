<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $selectedCategory = $request->string('category')->toString();
        $search = $request->string('search')->toString();

        $publicPlaylistScope = fn (Builder $query) => $query->where('is_public', true)->whereNotNull('approved_at');
        $baseQuery = fn () => Channel::query()
            ->where('is_active', true)
            ->whereHas('playlist', $publicPlaylistScope);

        $categories = Cache::remember('public-dashboard:categories', now()->addMinutes(10), fn () => $baseQuery()
            ->whereNotNull('group_title')
            ->distinct('group_title')
            ->orderBy('group_title')
            ->pluck('group_title'));

        $heroChannel = Cache::remember('public-dashboard:hero-channel', now()->addMinutes(5), fn () => $baseQuery()
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->with('playlist')
            ->first());

        $recommendedChannels = Cache::remember('public-dashboard:recommended', now()->addMinutes(5), fn () => $baseQuery()
            ->with('playlist')
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Channel $channel) => $this->serializeDashboardChannel($channel)));

        $sections = Cache::remember('public-dashboard:sections', now()->addMinutes(5), fn () => $baseQuery()
            ->whereNotNull('group_title')
            ->with('playlist')
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->limit(30)
            ->get()
            ->groupBy('group_title')
            ->take(4)
            ->map(fn ($group) => $group->take(4)));

        $channels = $baseQuery()
            ->with('playlist')
            ->when($selectedCategory !== '', fn (Builder $query) => $query->where('group_title', $selectedCategory))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString();

        $liveChannels = $channels->getCollection()
            ->map(fn (Channel $channel) => $this->serializeDashboardChannel($channel))
            ->values();

        return view('public.home', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'heroChannel' => $heroChannel,
            'sections' => $sections,
            'channels' => $channels,
            'liveChannels' => $liveChannels,
            'recommendedChannels' => $recommendedChannels,
        ]);
    }

    private function serializeDashboardChannel(Channel $channel): array
    {
        $viewerCount = 1200 + (($channel->id * 137) % 184000);

        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'streamer' => $channel->playlist?->name ?? 'RIFI Media',
            'category' => $channel->group_title ?: 'General',
            'thumbnail' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'avatar' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'viewers' => $viewerCount,
            'viewers_label' => $viewerCount >= 1000 ? round($viewerCount / 1000, 1).'K' : (string) $viewerCount,
            'watch_url' => route('channels.show', $channel),
        ];
    }
}

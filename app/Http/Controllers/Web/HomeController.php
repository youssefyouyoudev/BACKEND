<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Services\TheSportsDbService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HomeController extends Controller
{
    public function __invoke(Request $request, TheSportsDbService $sportsDb): View
    {
        $selectedCategory = $request->string('category')->toString();
        $search = $request->string('search')->toString();

        $publicPlaylistScope = fn (Builder $query) => $query->where('is_public', true)->whereNotNull('approved_at');
        $baseQuery = fn () => Channel::query()
            ->where('is_active', true)
            ->canonical()
            ->whereHas('playlist', $publicPlaylistScope);

        $categories = Cache::remember('public-dashboard:categories', now()->addMinutes(10), fn () => $baseQuery()
            ->whereNotNull('group_title')
            ->distinct('group_title')
            ->orderBy('group_title')
            ->pluck('group_title'));

        $heroChannel = Cache::remember('public-dashboard:hero-channel', now()->addMinutes(5), fn () => $baseQuery()
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->with(['category', 'playlist', 'currentProgram'])
            ->first());

        $recommendedChannels = Cache::remember('public-dashboard:recommended', now()->addMinutes(5), fn () => $baseQuery()
            ->with(['category', 'playlist', 'currentProgram'])
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Channel $channel) => $this->serializeDashboardChannel($channel)));

        $sections = Cache::remember('public-dashboard:sections', now()->addMinutes(5), fn () => $baseQuery()
            ->whereNotNull('group_title')
            ->with(['category', 'playlist', 'currentProgram'])
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->limit(30)
            ->get()
            ->groupBy('group_title')
            ->take(4)
            ->map(fn ($group) => $group->take(4)));

        $channels = $baseQuery()
            ->with(['category', 'playlist', 'currentProgram'])
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

        $footballMatches = collect();

        try {
            $footballMatches = collect($sportsDb->getTopLeagueMatchesByDate(now()->toDateString()))->take(4);
        } catch (Throwable) {
            $footballMatches = collect();
        }

        $articles = Schema::hasTable('articles')
            ? Article::query()->published()->with(['category', 'author'])->latest('published_at')->limit(4)->get()
            : collect();

        return view('public.home', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'heroChannel' => $heroChannel,
            'sections' => $sections,
            'channels' => $channels,
            'liveChannels' => $liveChannels,
            'recommendedChannels' => $recommendedChannels,
            'footballMatches' => $footballMatches,
            'articles' => $articles,
        ]);
    }

    private function serializeDashboardChannel(Channel $channel): array
    {
        $viewerCount = 1200 + (($channel->id * 137) % 184000);

        return [
            'id' => $channel->id,
            'name' => $channel->clean_display_name,
            'original_name' => $channel->name,
            'display_tags' => $channel->display_tags,
            'quality_label' => $channel->quality_label,
            'streamer' => $channel->playlist?->name ?? 'RIFI Media',
            'category' => $channel->category?->name ?? $channel->group_title ?: 'General',
            'program' => $channel->currentProgram ? [
                'title' => $channel->currentProgram->title,
                'start_time' => $channel->currentProgram->start_time?->format('H:i'),
                'end_time' => $channel->currentProgram->end_time?->format('H:i'),
            ] : null,
            'thumbnail' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'avatar' => $channel->logo ?: asset('brand/rifi-logo.png'),
            'viewers' => $viewerCount,
            'viewers_label' => $viewerCount >= 1000 ? round($viewerCount / 1000, 1).'K' : (string) $viewerCount,
            'watch_url' => route('channels.show', $channel),
        ];
    }
}

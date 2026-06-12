<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\WorldCupMatch;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $todayMatches = collect();
        $upcomingMatches = collect();
        $worldCupMatchesCount = 0;

        if (Schema::hasTable('world_cup_matches')) {
            $matchQuery = fn (): Builder => WorldCupMatch::query()
                ->publicVisible()
                ->with([
                    'selectedChannel.playlist',
                    'selectedChannel.category',
                    'selectedIptvItem.playlist',
                    'iptvItems.playlist',
                ]);

            $todayMatches = $matchQuery()
                ->whereDate('morocco_kickoff_at', now('Africa/Casablanca')->toDateString())
                ->orderBy('kickoff_at')
                ->limit(4)
                ->get();

            $upcomingMatches = $matchQuery()
                ->where('kickoff_at', '>=', now())
                ->orderBy('kickoff_at')
                ->limit(4)
                ->get();

            $worldCupMatchesCount = WorldCupMatch::query()->groupStage()->count();
        }

        return view('landing.index', [
            'todayMatches' => $todayMatches,
            'upcomingMatches' => $upcomingMatches,
            'previewMatches' => $todayMatches->isNotEmpty() ? $todayMatches : $upcomingMatches,
            'featuredChannels' => $this->featuredChannels(),
            'worldCupMatchesCount' => $worldCupMatchesCount,
            'schema' => $this->homeSchema(),
        ]);
    }

    /**
     * @return Collection<int, Channel>
     */
    private function featuredChannels(): Collection
    {
        if (! Schema::hasTable('channels') || ! Schema::hasTable('playlists')) {
            return collect();
        }

        return Channel::query()
            ->where('is_active', true)
            ->canonical()
            ->whereHas('playlist', fn (Builder $query) => $query
                ->where('is_public', true)
                ->whereNotNull('approved_at'))
            ->with(['category', 'playlist'])
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    private function homeSchema(): array
    {
        $faqItems = collect(__('landing.faq.items'));

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'RiFiTV',
                    'url' => url('/'),
                    'logo' => asset('brand/rifi-logo.png'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => 'RiFiTV',
                    'inLanguage' => app()->isLocale('ar') ? 'ar-MA' : 'en',
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqItems->map(fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $item['answer'],
                        ],
                    ])->values()->all(),
                ],
            ],
        ];
    }
}

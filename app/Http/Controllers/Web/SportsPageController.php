<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Models\WorldCupMatch;
use App\Services\TheSportsDbService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SportsPageController extends Controller
{
    public function news(): View
    {
        return view('public.news', [
            'topics' => $this->trendingTopics(),
            'articles' => $this->publishedArticles(),
        ]);
    }

    public function article(string $slug): View
    {
        abort_unless(Schema::hasTable('articles'), 404);

        $article = Article::query()
            ->published()
            ->with(['author', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $article->title,
            'description' => $article->meta_description ?: $article->excerpt,
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => $article->updated_at?->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $article->author?->name ?? 'RiFiTV Desk',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'RiFiTV',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('brand/rifi-logo.png'),
                ],
            ],
        ];

        return view('public.article', [
            'article' => $article,
            'relatedArticles' => $this->publishedArticles(4)->reject(fn (Article $related) => $related->id === $article->id)->take(3),
            'schema' => $schema,
        ]);
    }

    public function scores(): RedirectResponse
    {
        return redirect()->route('sports.football');
    }

    public function fixtures(): RedirectResponse
    {
        return redirect()->route('sports.football');
    }

    public function matches(): RedirectResponse
    {
        return redirect()->route('sports.football');
    }

    public function standings(): View
    {
        return view('public.standings', [
            'leagues' => $this->leagueDirectory(),
        ]);
    }

    public function leagues(): View
    {
        return view('public.directory', [
            'kind' => 'leagues',
            'items' => $this->leagueDirectory(),
            'title' => __('Football Leagues'),
            'description' => __('Follow football competition pages, standings, fixtures, and match coverage on RiFiTV.'),
        ]);
    }

    public function league(string $slug): View
    {
        $league = $this->leagueDirectory()->firstWhere('slug', $slug);
        abort_unless($league, 404);

        return view('public.match-center', [
            'mode' => 'league',
            'item' => $league,
            'relatedChannels' => $this->sportsChannels(6),
        ]);
    }

    public function teams(): View
    {
        return view('public.directory', [
            'kind' => 'teams',
            'items' => $this->teamDirectory(),
            'title' => __('Football Teams'),
            'description' => __('Browse football team pages for match updates, fixtures, team news, and coverage.'),
        ]);
    }

    public function team(string $slug): View
    {
        $team = $this->teamDirectory()->firstWhere('slug', $slug);
        abort_unless($team, 404);

        return view('public.match-center', [
            'mode' => 'team',
            'item' => $team,
            'relatedChannels' => $this->sportsChannels(6),
        ]);
    }

    public function match(string $slug, TheSportsDbService $sportsDb): View
    {
        $eventId = $this->extractSportsDbEventId($slug);

        return view('public.match-center', [
            'mode' => 'match',
            'item' => [
                'name' => Str::headline(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'event_id' => $eventId,
                'description' => __('Match center pages are ready for previews, lineups, timelines, stats, and related coverage when reliable match data is connected.'),
            ],
            'relatedChannels' => $this->sportsChannels(6),
            'tvChannels' => $eventId ? collect($sportsDb->tvChannelsForEvent($eventId)) : collect(),
        ]);
    }

    public function highlights(): View
    {
        return view('public.highlights', [
            'topics' => $this->trendingTopics(),
        ]);
    }

    public function tvGuide(Request $request): View
    {
        $matches = Schema::hasTable('world_cup_matches')
            ? WorldCupMatch::query()
                ->publicVisible()
                ->with(['selectedChannel', 'selectedIptvItem'])
                ->where('kickoff_at', '>=', now()->subHours(3))
                ->orderBy('kickoff_at')
                ->limit(60)
                ->get()
            : collect();

        return view('public.tv-guide', [
            'matches' => $matches,
            'region' => (string) $request->route('region', 'all'),
        ]);
    }

    public function search(Request $request): View
    {
        $query = Str::of($request->string('q')->toString())->squish()->limit(80, '')->toString();

        $channels = collect();
        $articles = collect();

        if ($query !== '') {
            $channels = Channel::query()
                ->where('is_active', true)
                ->canonical()
                ->whereHas('playlist', fn (Builder $playlistQuery) => $playlistQuery->where('is_public', true)->whereNotNull('approved_at'))
                ->where('name', 'like', '%'.$query.'%')
                ->with(['category', 'playlist', 'currentProgram'])
                ->limit(12)
                ->get();

            $articles = Schema::hasTable('articles')
                ? Article::query()
                    ->published()
                    ->where(function (Builder $articleQuery) use ($query): void {
                        $articleQuery->where('title', 'like', '%'.$query.'%')
                            ->orWhere('excerpt', 'like', '%'.$query.'%');
                    })
                    ->latest('published_at')
                    ->limit(8)
                    ->get()
                : collect();
        }

        return view('public.search', [
            'query' => $query,
            'channels' => $channels,
            'articles' => $articles,
            'pages' => $this->searchPages($query),
        ]);
    }

    public function staticPage(string $page): View
    {
        $pages = [
            'about' => [
                'title' => __('About RiFiTV'),
                'description' => __('RiFiTV is a football scores, schedules, news, match information, and TV guide platform for Morocco and MENA.'),
                'body' => __('RiFiTV brings football scores, fixtures, results, World Cup 2026 coverage, editorial news, and TV guide information into one clear platform.'),
            ],
            'contact' => [
                'title' => __('Contact RiFiTV'),
                'description' => __('Contact the RiFiTV team for editorial, rights, partnership, advertising, and platform questions.'),
                'body' => __('For editorial, rights, advertising, or technical questions, use the official contact method configured for RiFiTV.'),
            ],
            'privacy-policy' => [
                'title' => __('Privacy Policy'),
                'description' => __('Read the RiFiTV privacy policy.'),
                'body' => __('RiFiTV may use essential cookies, analytics, and advertising services. We limit data collection, protect account information, and explain available privacy choices.'),
            ],
            'terms' => [
                'title' => __('Terms of Use'),
                'description' => __('Read the RiFiTV terms of use.'),
                'body' => __('Users must use RiFiTV lawfully, respect intellectual property rights, avoid abuse, and follow all applicable terms for submitted or accessed content.'),
            ],
            'copyright' => [
                'title' => __('Copyright and DMCA'),
                'description' => __('Copyright, DMCA, and rights information for RiFiTV.'),
                'body' => __('RiFiTV respects copyright. Rights holders may request review or removal through the contact method on this website. Broadcast rights belong to their respective owners.'),
            ],
            'advertise' => [
                'title' => __('Advertise With RiFiTV'),
                'description' => __('Advertising and sponsorship opportunities across RiFiTV news, scores, fixtures, and match coverage.'),
                'body' => __('RiFiTV supports responsible sponsorships across editorial pages, scores, fixtures, competition pages, and match-center experiences.'),
            ],
            'editorial-policy' => [
                'title' => __('Editorial Policy'),
                'description' => __('RiFiTV editorial standards for football coverage.'),
                'body' => __('RiFiTV aims to publish accurate, clearly labeled football coverage. Articles should identify authors, dates, updates, sources, and corrections when needed.'),
            ],
        ];

        abort_unless(isset($pages[$page]), 404);

        return view('public.static-page', ['page' => $pages[$page], 'slug' => $page]);
    }

    public function sitemap(): Response
    {
        $urls = collect([
            route('home'),
            route('football.index'),
            route('football.today'),
            route('football.tomorrow'),
            route('football.results'),
            route('football.schedules'),
            route('world-cup-2026.index'),
            route('world-cup-2026.schedule'),
            route('world-cup-2026.groups'),
            route('world-cup-2026.morocco'),
            route('world-cup-2026.africa'),
            route('tv-guide.index'),
            route('tv-guide.morocco'),
            route('news.index'),
            route('competitions.index'),
            route('teams.index'),
            route('standings'),
            route('about'),
            route('contact'),
            route('privacy'),
            route('terms'),
            route('copyright'),
            route('advertise'),
            route('editorial-policy'),
        ])
            ->merge($this->publishedArticles(100)->map(fn (Article $article) => route('news.show', $article->slug)))
            ->merge($this->leagueDirectory()->map(fn ($league) => route('leagues.show', $league['slug'])))
            ->unique()
            ->values();

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        return response()
            ->view('public.robots')
            ->header('Content-Type', 'text/plain');
    }

    private function sportsChannels(int $limit): Collection
    {
        return Channel::query()
            ->where('is_active', true)
            ->canonical()
            ->whereHas('playlist', fn (Builder $query) => $query->where('is_public', true)->whereNotNull('approved_at'))
            ->where(function (Builder $query): void {
                $query->where('group_title', 'like', '%sport%')
                    ->orWhere('name', 'like', '%sport%')
                    ->orWhere('name', 'like', '%bein%')
                    ->orWhere('name', 'like', '%alwan%');
            })
            ->with(['category', 'playlist', 'currentProgram'])
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    private function extractSportsDbEventId(string $slug): ?string
    {
        if (preg_match('/\d+/', $slug, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    private function publishedArticles(int $limit = 12): Collection
    {
        if (! Schema::hasTable('articles')) {
            return collect();
        }

        return Article::query()
            ->published()
            ->with(['author', 'category'])
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    private function trendingTopics(): Collection
    {
        return collect(['Football', 'Transfers', 'Champions League', 'La Liga', 'Premier League', 'Botola', 'AFCON', 'Morocco National Team']);
    }

    private function searchPages(string $query): Collection
    {
        $pages = collect([
            ['title' => __('Football Scores'), 'description' => __('Today, upcoming, and recent football matches.'), 'url' => route('football.today')],
            ['title' => __('Football Schedules'), 'description' => __('Fixtures and kickoff times in Morocco time.'), 'url' => route('football.schedules')],
            ['title' => __('World Cup 2026'), 'description' => __('Fixtures, groups, Morocco coverage, and results.'), 'url' => route('world-cup-2026.index')],
            ['title' => __('TV Guide'), 'description' => __('Football broadcaster and schedule information.'), 'url' => route('tv-guide.index')],
            ['title' => __('News'), 'description' => __('Published RiFiTV football articles.'), 'url' => route('news.index')],
        ]);

        if ($query === '') {
            return collect();
        }

        return $pages
            ->filter(fn (array $page): bool => str_contains(Str::lower($page['title'].' '.$page['description']), Str::lower($query)))
            ->values();
    }

    private function leagueDirectory(): Collection
    {
        return Cache::remember('public-directory:football-leagues', now()->addDay(), fn () => collect(config('football_leagues.top_leagues', []))
            ->map(fn (array $league): array => [
                'name' => $league['name'],
                'slug' => $league['slug'],
                'region' => $league['country'],
            ]));
    }

    private function teamDirectory(): Collection
    {
        return collect();
    }
}

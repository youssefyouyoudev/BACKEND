<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Services\TheSportsDbService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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
                'name' => $article->author?->name ?? 'RifiMedia Sports Desk',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'RifiMedia Sports',
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
            'title' => 'Football Leagues',
            'description' => 'Follow football league pages, standings, fixtures, and match coverage on RifiMedia Sports.',
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
            'title' => 'Football Teams',
            'description' => 'Browse football team pages for match updates, fixtures, team news, and coverage.',
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
                'description' => 'Match center pages are ready for previews, lineups, timelines, stats, and related coverage when reliable match data is connected.',
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
                'title' => 'About RifiMedia Sports',
                'description' => 'RifiMedia Sports is a sports media platform for football news, fixtures, live score information, standings, and match updates.',
                'body' => 'RifiMedia Sports is being built around useful sports coverage: fixtures, scores, standings, match centers, articles, and responsible sports media experiences.',
            ],
            'contact' => [
                'title' => 'Contact RifiMedia Sports',
                'description' => 'Contact the RifiMedia Sports team for editorial, partnership, and platform questions.',
                'body' => 'For editorial, copyright, advertising, or technical questions, use the official contact channel configured for RifiMedia Sports.',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'description' => 'Read the RifiMedia Sports privacy policy.',
                'body' => 'This page explains the privacy principles for RifiMedia Sports, including data minimization, account security, analytics, and communication preferences.',
            ],
            'terms' => [
                'title' => 'Terms of Use',
                'description' => 'Read the RifiMedia Sports terms of use.',
                'body' => 'Users must use RifiMedia Sports lawfully and are responsible for ensuring they have rights to any playlist, stream source, or content they submit.',
            ],
            'copyright' => [
                'title' => 'Copyright and DMCA',
                'description' => 'Copyright and takedown information for RifiMedia Sports.',
                'body' => 'RifiMedia Sports respects copyright. Rights holders can request review or removal of allegedly infringing user-submitted sources through the configured contact process.',
            ],
            'advertise' => [
                'title' => 'Advertise With RifiMedia Sports',
                'description' => 'Advertising and sponsorship opportunities across RifiMedia Sports news, scores, fixtures, and match coverage.',
                'body' => 'RifiMedia Sports is prepared for responsible sponsorships across editorial pages, live scores, fixtures, league pages, and match center experiences.',
            ],
            'editorial-policy' => [
                'title' => 'Editorial Policy',
                'description' => 'RifiMedia Sports editorial standards for sports coverage.',
                'body' => 'RifiMedia Sports aims to publish accurate, clearly labeled, useful sports coverage. Future articles should identify authors, dates, updates, sources, and corrections when needed.',
            ],
        ];

        abort_unless(isset($pages[$page]), 404);

        return view('public.static-page', ['page' => $pages[$page], 'slug' => $page]);
    }

    public function sitemap(): Response
    {
        $urls = collect([
            route('home'),
            route('sports.index'),
            route('sports.football'),
            route('live-tv'),
            route('movies'),
            route('tv-shows'),
            route('anime'),
            route('news.index'),
            route('leagues.index'),
            route('standings'),
            route('highlights'),
            route('search'),
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
            ['title' => 'Live TV', 'description' => 'Browse approved live TV channels.', 'url' => route('live-tv')],
            ['title' => 'Football Scores', 'description' => 'Today, upcoming, and recent football matches.', 'url' => route('sports.football')],
            ['title' => 'Movies', 'description' => 'Coming soon.', 'url' => route('movies')],
            ['title' => 'TV Shows', 'description' => 'Coming soon.', 'url' => route('tv-shows')],
            ['title' => 'Anime', 'description' => 'Coming soon.', 'url' => route('anime')],
            ['title' => 'News', 'description' => 'Published RifiMedia articles.', 'url' => route('news.index')],
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
        return collect(config('football_leagues.top_leagues', []))
            ->map(fn (array $league): array => [
                'name' => $league['name'],
                'slug' => $league['slug'],
                'region' => $league['country'],
            ]);
    }

    private function teamDirectory(): Collection
    {
        return collect();
    }
}

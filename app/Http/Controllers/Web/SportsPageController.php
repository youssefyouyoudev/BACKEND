<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SportsPageController extends Controller
{
    public function news(): View
    {
        return view('public.news', [
            'topics' => $this->trendingTopics(),
            'articles' => collect(),
        ]);
    }

    public function article(string $slug): View
    {
        abort(404, 'This article has not been published yet.');
    }

    public function scores(): View
    {
        return view('public.scores', [
            'matches' => collect(),
            'leagues' => $this->leagueDirectory(),
        ]);
    }

    public function fixtures(): View
    {
        return view('public.fixtures', [
            'fixtures' => collect(),
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

    public function match(string $slug): View
    {
        return view('public.match-center', [
            'mode' => 'match',
            'item' => [
                'name' => Str::headline(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'description' => 'Match center pages are ready for previews, lineups, timelines, stats, and related coverage when reliable match data is connected.',
            ],
            'relatedChannels' => $this->sportsChannels(6),
        ]);
    }

    public function highlights(): View
    {
        return view('public.highlights', [
            'topics' => $this->trendingTopics(),
        ]);
    }

    public function search(): View
    {
        return view('public.search', [
            'channels' => $this->sportsChannels(12),
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
            route('scores'),
            route('fixtures'),
            route('news.index'),
            route('leagues.index'),
            route('teams.index'),
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
            ->merge($this->leagueDirectory()->map(fn ($league) => route('leagues.show', $league['slug'])))
            ->merge($this->teamDirectory()->map(fn ($team) => route('teams.show', $team['slug'])))
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

    private function trendingTopics(): Collection
    {
        return collect(['Football', 'Transfers', 'Champions League', 'La Liga', 'Premier League', 'Botola', 'AFCON', 'Morocco National Team']);
    }

    private function leagueDirectory(): Collection
    {
        return collect([
            ['name' => 'Premier League', 'slug' => 'premier-league', 'region' => 'England'],
            ['name' => 'La Liga', 'slug' => 'la-liga', 'region' => 'Spain'],
            ['name' => 'Champions League', 'slug' => 'champions-league', 'region' => 'Europe'],
            ['name' => 'Botola Pro', 'slug' => 'botola-pro', 'region' => 'Morocco'],
            ['name' => 'AFCON', 'slug' => 'afcon', 'region' => 'Africa'],
            ['name' => 'Serie A', 'slug' => 'serie-a', 'region' => 'Italy'],
        ]);
    }

    private function teamDirectory(): Collection
    {
        return collect([
            ['name' => 'Morocco National Team', 'slug' => 'morocco-national-team', 'region' => 'Morocco'],
            ['name' => 'Raja Club Athletic', 'slug' => 'raja-club-athletic', 'region' => 'Morocco'],
            ['name' => 'Wydad AC', 'slug' => 'wydad-ac', 'region' => 'Morocco'],
            ['name' => 'Real Madrid', 'slug' => 'real-madrid', 'region' => 'Spain'],
            ['name' => 'Barcelona', 'slug' => 'barcelona', 'region' => 'Spain'],
            ['name' => 'Manchester City', 'slug' => 'manchester-city', 'region' => 'England'],
        ]);
    }
}

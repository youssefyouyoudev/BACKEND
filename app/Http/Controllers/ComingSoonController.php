<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class ComingSoonController extends Controller
{
    public function __invoke(string $section): View
    {
        $sections = [
            'movies' => [
                'title' => 'Movies',
                'description' => 'We are preparing a better movie discovery and watching experience.',
                'features' => ['Movies library', 'Collections', 'Watchlist', 'Continue watching'],
            ],
            'tv-shows' => [
                'title' => 'TV Shows',
                'description' => 'We are preparing TV schedules, show pages, and simple browsing tools.',
                'features' => ['TV schedule', 'Show pages', 'Watchlist', 'Episode tracking'],
            ],
            'anime' => [
                'title' => 'Anime',
                'description' => 'We are preparing a cleaner anime experience with episodes and watchlists.',
                'features' => ['Anime episodes', 'Season guides', 'Watchlist', 'Release calendar'],
            ],
        ];

        abort_unless(isset($sections[$section]), 404);

        return view('public.coming-soon', [
            'section' => $section,
            'page' => $sections[$section],
            'slug' => Str::slug($sections[$section]['title']),
        ]);
    }
}

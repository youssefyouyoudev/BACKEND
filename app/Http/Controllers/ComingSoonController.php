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
                'description' => 'Movie discovery is being shaped into a cleaner premium RifiMedia experience.',
                'features' => ['Movies library', 'Collections', 'Watchlist', 'Continue watching'],
            ],
            'tv-shows' => [
                'title' => 'TV Shows',
                'description' => 'TV schedules, show pages, and browsing tools are being shaped for RifiMedia users.',
                'features' => ['TV schedule', 'Show pages', 'Watchlist', 'Episode tracking'],
            ],
            'anime' => [
                'title' => 'Anime',
                'description' => 'Anime episodes, season guides, and watchlists are being shaped for RifiMedia users.',
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

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Top Football Leagues
    |--------------------------------------------------------------------------
    |
    | TheSportsDB v1 endpoints need numeric league IDs. These IDs were checked
    | against the v1 documentation endpoints:
    | - all_leagues.php for the main European leagues
    | - search_all_leagues.php?c={country}&s=Soccer for MLS and Saudi
    |
    | You can add/remove leagues here without changing code. Missing IDs are
    | skipped safely by the service.
    |
    */
    'top_leagues' => [
        [
            'id' => 4328,
            'name' => 'English Premier League',
            'country' => 'England',
            'slug' => 'premier-league',
        ],
        [
            'id' => 4335,
            'name' => 'Spanish La Liga',
            'country' => 'Spain',
            'slug' => 'la-liga',
        ],
        [
            'id' => 4332,
            'name' => 'Italian Serie A',
            'country' => 'Italy',
            'slug' => 'serie-a',
        ],
        [
            'id' => 4331,
            'name' => 'German Bundesliga',
            'country' => 'Germany',
            'slug' => 'bundesliga',
        ],
        [
            'id' => 4334,
            'name' => 'French Ligue 1',
            'country' => 'France',
            'slug' => 'ligue-1',
        ],
        [
            'id' => 4346,
            'name' => 'American Major League Soccer',
            'country' => 'United States',
            'slug' => 'mls',
        ],
        [
            'id' => 4668,
            'name' => 'Saudi-Arabian Pro League',
            'country' => 'Saudi Arabia',
            'slug' => 'saudi-pro-league',
        ],
        [
            'id' => 4429,
            'name' => 'FIFA World Cup',
            'country' => 'World',
            'slug' => 'fifa-world-cup',
        ],
    ],
];

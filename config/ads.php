<?php

return [
    'enabled' => filter_var(
        env('ADS_ENABLED', env('RIFITV_ADS_ENABLED', env('VITE_ADS_ENABLED', true))),
        FILTER_VALIDATE_BOOL
    ),
    'runtime_enabled' => filter_var(
        env('ADS_RUNTIME_ENABLED', false),
        FILTER_VALIDATE_BOOL
    ),
    'provider' => env('ADS_PROVIDER', 'placeholder'),
    'adsense_client' => env('ADSENSE_CLIENT'),
    'sponsor_url' => env('SPONSOR_URL', env('VITE_MONETAG_SMARTLINK', 'https://omg10.com/4/11137969')),
    'smartlink_url' => env('MONETAG_SMARTLINK_URL', env('VITE_MONETAG_SMARTLINK', 'https://omg10.com/4/11137969')),
    'rifimedia_popup' => [
        'enabled' => filter_var(env('RIFIMEDIA_POPUP_ENABLED', true), FILTER_VALIDATE_BOOL),
        'title' => env('RIFIMEDIA_POPUP_TITLE', 'RifiMedia Premium'),
        'message' => env('RIFIMEDIA_POPUP_MESSAGE', 'بغيتي جودة أحسن وتجربة مستقرة بلا تقطاع؟ دخل لـ RifiMedia وشوف العروض المتوفرة.'),
        'url' => env('RIFIMEDIA_POPUP_URL', 'https://rifimedia.com'),
        'frequency_hours' => (int) env('RIFIMEDIA_POPUP_FREQUENCY_HOURS', 24),
    ],
    'monetag' => [
        'zone_11137947' => [
            'enabled' => filter_var(env('VITE_MONETAG_ZONE_11137947', true), FILTER_VALIDATE_BOOL),
            'zone' => '11137947',
            'src' => 'https://al5sm.com/tag.min.js',
        ],
        'zone_11137952' => [
            'enabled' => filter_var(env('VITE_MONETAG_ZONE_11137952', true), FILTER_VALIDATE_BOOL),
            'zone' => '11137952',
            'src' => 'https://nap5k.com/tag.min.js',
        ],
        'vignette_11137954' => [
            'enabled' => filter_var(env('VITE_MONETAG_VIGNETTE_11137954', true), FILTER_VALIDATE_BOOL),
            'zone' => '11137954',
            'src' => 'https://n6wxm.com/vignette.min.js',
        ],
    ],
];

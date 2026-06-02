<?php

$frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
$androidOrigins = array_filter(array_map('trim', explode(',', (string) env('ANDROID_ALLOWED_ORIGINS', ''))));

return [
    'paths' => ['api/*', 'football/api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_merge([
        $frontendUrl,
        'https://live.rifimedia.com',
        'http://127.0.0.1:5173',
        'http://localhost:4173',
    ], $androidOrigins))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];

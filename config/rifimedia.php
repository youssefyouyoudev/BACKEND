<?php

return [
    'force_https' => env('RIFIMEDIA_FORCE_HTTPS', str_starts_with((string) env('APP_URL', ''), 'https://')),
    'stream_bridge' => [
        'enabled' => env('RIFIMEDIA_STREAM_BRIDGE_ENABLED', true),
    ],
    'admin' => [
        'email' => env('RIFIMEDIA_ADMIN_EMAIL', 'admin@rifimedia.test'),
        'name' => env('RIFIMEDIA_ADMIN_NAME', 'RiFiMedia Admin'),
        'password' => env('RIFIMEDIA_ADMIN_PASSWORD'),
    ],
    'playlists' => [
        'max_download_kb' => env('RIFIMEDIA_PLAYLIST_MAX_DOWNLOAD_KB', 5120),
        'max_channels_per_import' => env('RIFIMEDIA_PLAYLIST_MAX_CHANNELS', 20000),
    ],
];

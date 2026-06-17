<?php

return [
    'enabled' => filter_var(
        env('ADS_ENABLED', env('RIFITV_ADS_ENABLED', true)),
        FILTER_VALIDATE_BOOL
    ),
    'runtime_enabled' => filter_var(
        env('ADS_RUNTIME_ENABLED', false),
        FILTER_VALIDATE_BOOL
    ),
    'provider' => env('ADS_PROVIDER', 'placeholder'),
    'adsense_client' => env('ADSENSE_CLIENT'),
    'sponsor_url' => env('SPONSOR_URL', 'https://omg10.com/4/11137969'),
];

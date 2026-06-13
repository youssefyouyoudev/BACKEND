<?php

return [
    'enabled' => filter_var(
        env('RIFITV_ADS_ENABLED', env('ADS_ENABLED', true)),
        FILTER_VALIDATE_BOOL
    ),
    'provider' => env('ADS_PROVIDER', 'placeholder'),
    'adsense_client' => env('ADSENSE_CLIENT'),
    'sponsor_url' => env('SPONSOR_URL', 'https://omg10.com/4/11137969'),
];

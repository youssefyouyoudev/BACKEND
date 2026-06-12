<?php

return [
    'enabled' => (bool) env('ADS_ENABLED', false),
    'provider' => env('ADS_PROVIDER', 'placeholder'),
    'adsense_client' => env('ADSENSE_CLIENT'),
    'sponsor_url' => env('SPONSOR_URL', 'https://omg10.com/4/11137969'),
];

<?php

return [
    'enabled' => (bool) env('ADS_ENABLED', false),
    'provider' => env('ADS_PROVIDER', 'placeholder'),
    'adsense_client' => env('ADSENSE_CLIENT'),
];

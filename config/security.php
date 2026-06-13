<?php

$adsCompatibleCsp = filter_var(env('CSP_ADS_COMPATIBLE', true), FILTER_VALIDATE_BOOL);

$strictPolicy = implode(' ', [
    "default-src 'self';",
    "base-uri 'self';",
    "frame-ancestors 'self';",
    "object-src 'none';",
    "form-action 'self';",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://static.cloudflareinsights.com blob:;",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com;",
    "img-src 'self' data: https: blob:;",
    "font-src 'self' data: https://fonts.gstatic.com;",
    "connect-src 'self' https: wss: blob:;",
    "frame-src 'self' https: blob:;",
    "media-src 'self' https: blob: data:;",
    "worker-src 'self' blob:;",
    "child-src 'self' https: blob:;",
]);

$adsCompatiblePolicy = implode(' ', [
    "default-src 'self' https: data: blob:;",
    "base-uri 'self';",
    "frame-ancestors 'self';",
    "object-src 'none';",
    "form-action 'self' https:;",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:;",
    "style-src 'self' 'unsafe-inline' https:;",
    "img-src 'self' data: https: blob:;",
    "font-src 'self' data: https:;",
    "connect-src 'self' https: wss: blob:;",
    "frame-src 'self' https: blob:;",
    "media-src 'self' https: blob: data:;",
    "worker-src 'self' blob:;",
    "child-src 'self' https: blob:;",
]);

return [
    'csp_ads_compatible' => $adsCompatibleCsp,
    'content_security_policy' => env('CONTENT_SECURITY_POLICY'),
    'strict_content_security_policy' => $strictPolicy,
    'ads_compatible_content_security_policy' => $adsCompatiblePolicy,
    'permissions_policy' => env(
        'PERMISSIONS_POLICY',
        'camera=(), microphone=(), geolocation=(), payment=(), bluetooth=()'
    ),
];

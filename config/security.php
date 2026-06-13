<?php

return [
    'content_security_policy' => env(
        'CONTENT_SECURITY_POLICY',
        "default-src 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'; form-action 'self'; img-src 'self' data: https:; media-src 'self' blob: https:; connect-src 'self' https://cloudflareinsights.com https://static.cloudflareinsights.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://static.cloudflareinsights.com https://omg10.com https://n6wxm.com https://nap5k.com https://al5sm.com https://5gvci.com https://quge5.com; script-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://static.cloudflareinsights.com https://omg10.com https://n6wxm.com https://nap5k.com https://al5sm.com https://5gvci.com https://quge5.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com;"
    ),
];

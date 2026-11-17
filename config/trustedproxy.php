<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs/CIDRs of reverse proxies in front of the app, read by
    | Illuminate\Http\Middleware\TrustProxies. Their X-Forwarded-* headers decide
    | the scheme, host and client IP, so production behind the Apache proxy
    | generates https://pesats.ksdae.kehutanan.go.id/... links. Leave empty
    | when the app is reached directly.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];

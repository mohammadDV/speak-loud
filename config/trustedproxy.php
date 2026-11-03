<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | When the application sits behind a TLS-terminating load balancer or CDN,
    | set TRUSTED_PROXIES so Laravel trusts forwarded scheme/host/IP headers.
    | Use "*" for cloud balancers with dynamic IPs, or a comma-separated list
    | of IP addresses / CIDR ranges (e.g. "10.0.0.0/8,192.168.1.1").
    |
    | Leave empty for local development without a reverse proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];

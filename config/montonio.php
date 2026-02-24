<?php

declare(strict_types=1);

return [
    'access_key' => env('MONTONIO_ACCESS_KEY', ''),
    'secret_key' => env('MONTONIO_SECRET_KEY', ''),
    'environment' => env('MONTONIO_ENVIRONMENT', 'sandbox'),
    'timeout' => env('MONTONIO_TIMEOUT', 30),

    'cache' => [
        'enabled' => env('MONTONIO_CACHE_ENABLED', true),
        'ttl' => env('MONTONIO_CACHE_TTL', 3600),
    ],

    'webhooks' => [
        'route' => env('MONTONIO_WEBHOOK_ROUTE', '/montonio/webhook'),
        'middleware' => [],
        'queue' => env('MONTONIO_WEBHOOK_QUEUE'),
    ],
];

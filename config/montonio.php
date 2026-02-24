<?php

declare(strict_types=1);

return [
    'access_key' => env('MONTONIO_ACCESS_KEY', ''),
    'secret_key' => env('MONTONIO_SECRET_KEY', ''),
    'environment' => env('MONTONIO_ENVIRONMENT', 'sandbox'),
    'timeout' => env('MONTONIO_TIMEOUT', 30),

    'migrations' => env('MONTONIO_MIGRATIONS_ENABLED', true),

    'models' => [
        'payment_method' => Veltix\LaravelMontonio\Models\PaymentMethod::class,
        'shipping_method' => Veltix\LaravelMontonio\Models\ShippingMethod::class,
    ],

    'sync_commands' => env('MONTONIO_SYNC_COMMANDS_ENABLED', true),

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

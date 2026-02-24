<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Veltix\Montonio\Montonio;

it('merges default config values', function (): void {
    expect(config('montonio.access_key'))->toBe('test_access_key')
        ->and(config('montonio.secret_key'))->toBe('test_secret_key_long_enough_for_hmac256')
        ->and(config('montonio.environment'))->toBe('sandbox')
        ->and(config('montonio.cache.enabled'))->toBeTrue()
        ->and(config('montonio.cache.ttl'))->toBe(3600)
        ->and(config('montonio.webhooks.route'))->toBe('/montonio/webhook');
});

it('binds Montonio as singleton', function (): void {
    $instance1 = $this->app->make(Montonio::class);
    $instance2 = $this->app->make(Montonio::class);

    expect($instance1)->toBe($instance2)
        ->and($instance1)->toBeInstanceOf(Montonio::class);
});

it('registers webhook route', function (): void {
    $route = Route::getRoutes()->getByName('montonio.webhook');

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->uri())->toBe('montonio/webhook');
});

it('registers artisan commands', function (): void {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('montonio:install')
        ->and($commands)->toHaveKey('montonio:sync-methods');
});

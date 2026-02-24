<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Veltix\LaravelMontonio\MontonioServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MontonioServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('montonio.access_key', 'test_access_key');
        $app['config']->set('montonio.secret_key', 'test_secret_key_long_enough_for_hmac256');
        $app['config']->set('montonio.environment', 'sandbox');
        $app['config']->set('montonio.timeout', 30);
        $app['config']->set('montonio.cache.enabled', true);
        $app['config']->set('montonio.cache.ttl', 3600);
        $app['config']->set('montonio.webhooks.queue', null);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

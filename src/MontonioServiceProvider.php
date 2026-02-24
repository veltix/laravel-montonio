<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Veltix\LaravelMontonio\Console\InstallCommand;
use Veltix\LaravelMontonio\Console\SyncMethodsCommand;
use Veltix\LaravelMontonio\Http\Controllers\WebhookController;
use Veltix\LaravelMontonio\Support\ConfigFactory;
use Veltix\Montonio\Montonio;

final class MontonioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/montonio.php', 'montonio');

        $this->app->singleton(Montonio::class, fn (): Montonio => new Montonio(ConfigFactory::make()));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/montonio.php' => config_path('montonio.php'),
        ], 'montonio-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'montonio-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SyncMethodsCommand::class,
            ]);
        }

        /** @var string $route */
        $route = config('montonio.webhooks.route');

        /** @var array<string>|string|null $middleware */
        $middleware = config('montonio.webhooks.middleware');

        $webhookRoute = Route::post($route, WebhookController::class);
        $webhookRoute->middleware($middleware);
        $webhookRoute->name('montonio.webhook');
    }
}

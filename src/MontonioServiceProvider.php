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

        $this->app->singleton(Montonio::class, fn (): \Veltix\Montonio\Montonio => new Montonio(ConfigFactory::make()));
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

        Route::post(config('montonio.webhooks.route'), WebhookController::class)
            ->middleware(config('montonio.webhooks.middleware'))
            ->name('montonio.webhook');
    }
}

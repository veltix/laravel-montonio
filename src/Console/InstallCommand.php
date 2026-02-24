<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'montonio:install {--skip-migrations : Skip publishing migrations (use your own tables and models)}';

    protected $description = 'Install the Montonio package configuration and migrations';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'montonio-config',
        ]);

        $skipMigrations = $this->option('skip-migrations') || ! config('montonio.migrations');

        if ($skipMigrations) {
            $this->info('Skipping migrations.');
        } else {
            $this->call('vendor:publish', [
                '--tag' => 'montonio-migrations',
            ]);
        }

        $this->info('Montonio package installed successfully.');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('  1. Set MONTONIO_ACCESS_KEY and MONTONIO_SECRET_KEY in your .env file');

        if (! $skipMigrations) {
            $this->info('  2. Run `php artisan migrate` to create the required tables');
        }

        if (config('montonio.sync_commands')) {
            $this->info('  3. Run `php artisan montonio:sync-methods` to sync payment and shipping methods');
        }

        return self::SUCCESS;
    }
}

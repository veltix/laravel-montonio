<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'montonio:install';

    protected $description = 'Install the Montonio package configuration and migrations';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'montonio-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'montonio-migrations',
        ]);

        $this->info('Montonio package installed successfully.');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('  1. Set MONTONIO_ACCESS_KEY and MONTONIO_SECRET_KEY in your .env file');
        $this->info('  2. Run `php artisan migrate` to create the required tables');
        $this->info('  3. Run `php artisan montonio:sync-methods` to sync payment and shipping methods');

        return self::SUCCESS;
    }
}

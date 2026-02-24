<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Console;

use Illuminate\Console\Command;
use Veltix\LaravelMontonio\Actions\SyncPaymentMethods;
use Veltix\LaravelMontonio\Actions\SyncShippingMethods;

final class SyncMethodsCommand extends Command
{
    protected $signature = 'montonio:sync-methods
                            {--payments-only : Only sync payment methods}
                            {--shipping-only : Only sync shipping methods}';

    protected $description = 'Sync payment and shipping methods from the Montonio API';

    public function handle(SyncPaymentMethods $syncPayments, SyncShippingMethods $syncShipping): int
    {
        $paymentsOnly = $this->option('payments-only');
        $shippingOnly = $this->option('shipping-only');

        if (! $shippingOnly) {
            $this->info('Syncing payment methods...');
            $result = $syncPayments->handle();
            $this->info(sprintf('Payment methods: %d created, %d updated, %d deactivated', $result->created, $result->updated, $result->deactivated));
        }

        if (! $paymentsOnly) {
            $this->info('Syncing shipping methods...');
            $result = $syncShipping->handle();
            $this->info(sprintf('Shipping methods: %d created, %d updated, %d deactivated', $result->created, $result->updated, $result->deactivated));
        }

        return self::SUCCESS;
    }
}

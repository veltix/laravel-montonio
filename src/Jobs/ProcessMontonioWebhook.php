<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Veltix\LaravelMontonio\Support\WebhookDispatcher;
use Veltix\Montonio\Montonio;

final class ProcessMontonioWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public readonly string $type,
        public readonly string $token,
    ) {}

    public function handle(Montonio $montonio, WebhookDispatcher $dispatcher): void
    {
        $webhooks = $montonio->webhooks();

        match ($this->type) {
            'payment' => $dispatcher->dispatchPayment(
                $webhooks->verifyPaymentWebhook($this->token),
            ),
            'shipping' => $dispatcher->dispatchShipping(
                $webhooks->verifyShippingWebhook($this->token),
            ),
        };
    }
}

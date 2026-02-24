<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Veltix\Montonio\Webhook\Dto\ShippingWebhookPayload;

final readonly class ShipmentLabelsCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ShippingWebhookPayload $payload,
    ) {}
}

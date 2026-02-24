<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Support;

use Veltix\LaravelMontonio\Events\LabelFileCreationFailed;
use Veltix\LaravelMontonio\Events\LabelFileReady;
use Veltix\LaravelMontonio\Events\PaymentWebhookReceived;
use Veltix\LaravelMontonio\Events\ShipmentLabelsCreated;
use Veltix\LaravelMontonio\Events\ShipmentRegistered;
use Veltix\LaravelMontonio\Events\ShipmentRegistrationFailed;
use Veltix\LaravelMontonio\Events\ShipmentStatusUpdated;
use Veltix\Montonio\Shipping\Enum\ShippingWebhookEvent;
use Veltix\Montonio\Webhook\Dto\PaymentWebhookPayload;
use Veltix\Montonio\Webhook\Dto\ShippingWebhookPayload;

final class WebhookDispatcher
{
    public function dispatchPayment(PaymentWebhookPayload $payload): void
    {
        PaymentWebhookReceived::dispatch($payload);
    }

    public function dispatchShipping(ShippingWebhookPayload $payload): void
    {
        $eventClass = match ($payload->eventType) {
            ShippingWebhookEvent::ShipmentRegistered => ShipmentRegistered::class,
            ShippingWebhookEvent::ShipmentRegistrationFailed => ShipmentRegistrationFailed::class,
            ShippingWebhookEvent::ShipmentStatusUpdated => ShipmentStatusUpdated::class,
            ShippingWebhookEvent::ShipmentLabelsCreated => ShipmentLabelsCreated::class,
            ShippingWebhookEvent::LabelFileReady => LabelFileReady::class,
            ShippingWebhookEvent::LabelFileCreationFailed => LabelFileCreationFailed::class,
        };

        $eventClass::dispatch($payload);
    }
}

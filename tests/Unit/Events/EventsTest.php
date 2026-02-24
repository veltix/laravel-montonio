<?php

declare(strict_types=1);

use Veltix\LaravelMontonio\Events\LabelFileCreationFailed;
use Veltix\LaravelMontonio\Events\LabelFileReady;
use Veltix\LaravelMontonio\Events\PaymentWebhookReceived;
use Veltix\LaravelMontonio\Events\ShipmentLabelsCreated;
use Veltix\LaravelMontonio\Events\ShipmentRegistered;
use Veltix\LaravelMontonio\Events\ShipmentRegistrationFailed;
use Veltix\LaravelMontonio\Events\ShipmentStatusUpdated;
use Veltix\Montonio\Payments\Enum\PaymentStatus;
use Veltix\Montonio\Webhook\Dto\PaymentWebhookPayload;
use Veltix\Montonio\Webhook\Dto\ShippingWebhookPayload;
use Veltix\Montonio\Shipping\Enum\ShippingWebhookEvent;

it('PaymentWebhookReceived holds payment payload', function (): void {
    $payload = new PaymentWebhookPayload(
        uuid: 'uuid-1',
        accessKey: 'key',
        merchantReference: 'REF-1',
        merchantReferenceDisplay: 'REF-1',
        paymentStatus: PaymentStatus::PAID,
        paymentMethod: 'paymentInitiation',
        grandTotal: 42.50,
        currency: 'EUR',
        senderIban: null,
        senderName: null,
        paymentProviderName: null,
        paymentLinkUuid: null,
        iat: time(),
        exp: time() + 3600,
    );

    $event = new PaymentWebhookReceived($payload);

    expect($event->payload)->toBe($payload)
        ->and($event->payload->uuid)->toBe('uuid-1')
        ->and($event->payload->paymentStatus)->toBe(PaymentStatus::PAID);
});

it('shipping events hold shipping payload', function (string $eventClass): void {
    $payload = new ShippingWebhookPayload(
        eventId: 'evt-1',
        shipmentId: 'ship-1',
        created: '2024-06-13T10:51:57.322Z',
        data: ['status' => 'registered'],
        eventType: ShippingWebhookEvent::ShipmentRegistered,
        iat: time(),
        exp: time() + 3600,
    );

    $event = new $eventClass($payload);

    expect($event->payload)->toBe($payload)
        ->and($event->payload->eventId)->toBe('evt-1');
})->with([
    ShipmentRegistered::class,
    ShipmentRegistrationFailed::class,
    ShipmentStatusUpdated::class,
    ShipmentLabelsCreated::class,
    LabelFileReady::class,
    LabelFileCreationFailed::class,
]);

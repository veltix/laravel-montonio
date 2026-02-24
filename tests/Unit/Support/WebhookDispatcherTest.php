<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Veltix\LaravelMontonio\Events\LabelFileCreationFailed;
use Veltix\LaravelMontonio\Events\LabelFileReady;
use Veltix\LaravelMontonio\Events\PaymentWebhookReceived;
use Veltix\LaravelMontonio\Events\ShipmentLabelsCreated;
use Veltix\LaravelMontonio\Events\ShipmentRegistered;
use Veltix\LaravelMontonio\Events\ShipmentRegistrationFailed;
use Veltix\LaravelMontonio\Events\ShipmentStatusUpdated;
use Veltix\LaravelMontonio\Support\WebhookDispatcher;
use Veltix\Montonio\Payments\Enum\PaymentStatus;
use Veltix\Montonio\Shipping\Enum\ShippingWebhookEvent;
use Veltix\Montonio\Webhook\Dto\PaymentWebhookPayload;
use Veltix\Montonio\Webhook\Dto\ShippingWebhookPayload;

it('dispatches PaymentWebhookReceived event', function (): void {
    Event::fake(PaymentWebhookReceived::class);

    $payload = new PaymentWebhookPayload(
        uuid: 'test-uuid',
        accessKey: 'test_access_key',
        merchantReference: 'ORDER-123',
        merchantReferenceDisplay: 'ORDER-123',
        paymentStatus: PaymentStatus::PAID,
        paymentMethod: 'paymentInitiation',
        grandTotal: 99.99,
        currency: 'EUR',
        senderIban: null,
        senderName: null,
        paymentProviderName: null,
        paymentLinkUuid: null,
        iat: time(),
        exp: time() + 3600,
    );

    (new WebhookDispatcher())->dispatchPayment($payload);

    Event::assertDispatched(PaymentWebhookReceived::class, fn ($event): bool => $event->payload === $payload);
});

it('dispatches the correct shipping event for each type', function (ShippingWebhookEvent $eventType, string $expectedClass): void {
    Event::fake();

    $payload = new ShippingWebhookPayload(
        eventId: 'evt-123',
        shipmentId: 'ship-456',
        created: '2024-06-13T10:51:57.322Z',
        data: ['status' => 'registered'],
        eventType: $eventType,
        iat: time(),
        exp: time() + 3600,
    );

    (new WebhookDispatcher())->dispatchShipping($payload);

    Event::assertDispatched($expectedClass, fn ($event): bool => $event->payload === $payload);
})->with([
    'ShipmentRegistered' => [ShippingWebhookEvent::ShipmentRegistered, ShipmentRegistered::class],
    'ShipmentRegistrationFailed' => [ShippingWebhookEvent::ShipmentRegistrationFailed, ShipmentRegistrationFailed::class],
    'ShipmentStatusUpdated' => [ShippingWebhookEvent::ShipmentStatusUpdated, ShipmentStatusUpdated::class],
    'ShipmentLabelsCreated' => [ShippingWebhookEvent::ShipmentLabelsCreated, ShipmentLabelsCreated::class],
    'LabelFileReady' => [ShippingWebhookEvent::LabelFileReady, LabelFileReady::class],
    'LabelFileCreationFailed' => [ShippingWebhookEvent::LabelFileCreationFailed, LabelFileCreationFailed::class],
]);

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Veltix\LaravelMontonio\Events\PaymentWebhookReceived;
use Veltix\LaravelMontonio\Events\ShipmentRegistered;
use Veltix\LaravelMontonio\Jobs\ProcessMontonioWebhook;
use Veltix\Montonio\Montonio;
use Veltix\Montonio\Shipping\Enum\ShippingWebhookEvent;

use function Veltix\LaravelMontonio\Tests\encodePaymentToken;
use function Veltix\LaravelMontonio\Tests\encodeShippingToken;
use function Veltix\LaravelMontonio\Tests\montonioWithResponses;

beforeEach(function (): void {
    // Bind a real Montonio instance (no HTTP needed for webhook verification)
    $this->app->instance(Montonio::class, montonioWithResponses());
});

it('verifies and dispatches payment webhook event', function (): void {
    Event::fake(PaymentWebhookReceived::class);

    $token = encodePaymentToken();
    $job = new ProcessMontonioWebhook('payment', $token);

    $this->app->call($job->handle(...));

    Event::assertDispatched(PaymentWebhookReceived::class, fn ($event): bool => $event->payload->merchantReference === 'MY-ORDER-ID-123'
        && $event->payload->grandTotal === 99.99);
});

it('verifies and dispatches shipping webhook event', function (): void {
    Event::fake(ShipmentRegistered::class);

    $token = encodeShippingToken(ShippingWebhookEvent::ShipmentRegistered);
    $job = new ProcessMontonioWebhook('shipping', $token);

    $this->app->call($job->handle(...));

    Event::assertDispatched(ShipmentRegistered::class, fn ($event): bool => $event->payload->eventType === ShippingWebhookEvent::ShipmentRegistered);
});

it('throws on invalid payment token', function (): void {
    $job = new ProcessMontonioWebhook('payment', 'invalid.jwt.token');

    expect(fn () => $this->app->call($job->handle(...)))->toThrow(Exception::class);
});

it('throws on invalid shipping token', function (): void {
    $job = new ProcessMontonioWebhook('shipping', 'invalid.jwt.token');

    expect(fn () => $this->app->call($job->handle(...)))->toThrow(Exception::class);
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Veltix\LaravelMontonio\Events\LabelFileCreationFailed;
use Veltix\LaravelMontonio\Events\LabelFileReady;
use Veltix\LaravelMontonio\Events\PaymentWebhookReceived;
use Veltix\LaravelMontonio\Events\ShipmentLabelsCreated;
use Veltix\LaravelMontonio\Events\ShipmentRegistered;
use Veltix\LaravelMontonio\Events\ShipmentRegistrationFailed;
use Veltix\LaravelMontonio\Events\ShipmentStatusUpdated;
use Veltix\LaravelMontonio\Jobs\ProcessMontonioWebhook;
use Veltix\Montonio\Montonio;
use Veltix\Montonio\Shipping\Enum\ShippingWebhookEvent;

use function Veltix\LaravelMontonio\Tests\encodePaymentToken;
use function Veltix\LaravelMontonio\Tests\encodeShippingToken;
use function Veltix\LaravelMontonio\Tests\montonioWithResponses;

beforeEach(function (): void {
    $this->app->instance(Montonio::class, montonioWithResponses());
});

// ─── Sync path (payment) ──────────────────────────────────

it('returns 200 and dispatches event for valid payment webhook', function (): void {
    Event::fake(PaymentWebhookReceived::class);

    $token = encodePaymentToken();

    $this->postJson(route('montonio.webhook'), ['orderToken' => $token])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    Event::assertDispatched(PaymentWebhookReceived::class, fn ($e): bool => $e->payload->merchantReference === 'MY-ORDER-ID-123');
});

it('returns 403 for invalid payment signature', function (): void {
    $this->postJson(route('montonio.webhook'), ['orderToken' => 'invalid.jwt.token'])
        ->assertStatus(403)
        ->assertJson(['error' => 'Invalid signature']);
});

it('returns 422 for empty payload', function (): void {
    $this->postJson(route('montonio.webhook'), [])
        ->assertStatus(422)
        ->assertJson(['error' => 'Invalid webhook payload']);
});

// ─── Sync path (shipping) ──────────────────────────────────

it('returns 200 and dispatches event for valid shipping webhook', function (): void {
    Event::fake(ShipmentRegistered::class);

    $token = encodeShippingToken(ShippingWebhookEvent::ShipmentRegistered);

    $this->postJson(route('montonio.webhook'), ['payload' => $token])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    Event::assertDispatched(ShipmentRegistered::class);
});

it('returns 403 for invalid shipping signature', function (): void {
    $this->postJson(route('montonio.webhook'), ['payload' => 'invalid.jwt.token'])
        ->assertStatus(403)
        ->assertJson(['error' => 'Invalid signature']);
});

it('dispatches correct event for each shipping event type', function (ShippingWebhookEvent $eventType, string $expectedClass): void {
    Event::fake();

    $token = encodeShippingToken($eventType);

    $this->postJson(route('montonio.webhook'), ['payload' => $token])
        ->assertOk();

    Event::assertDispatched($expectedClass);
})->with([
    'ShipmentRegistered' => [ShippingWebhookEvent::ShipmentRegistered, ShipmentRegistered::class],
    'ShipmentRegistrationFailed' => [ShippingWebhookEvent::ShipmentRegistrationFailed, ShipmentRegistrationFailed::class],
    'ShipmentStatusUpdated' => [ShippingWebhookEvent::ShipmentStatusUpdated, ShipmentStatusUpdated::class],
    'ShipmentLabelsCreated' => [ShippingWebhookEvent::ShipmentLabelsCreated, ShipmentLabelsCreated::class],
    'LabelFileReady' => [ShippingWebhookEvent::LabelFileReady, LabelFileReady::class],
    'LabelFileCreationFailed' => [ShippingWebhookEvent::LabelFileCreationFailed, LabelFileCreationFailed::class],
]);

// ─── Queue path ────────────────────────────────────────────

it('queues payment webhook when queue configured', function (): void {
    Bus::fake(ProcessMontonioWebhook::class);
    config()->set('montonio.webhooks.queue', 'webhooks');

    $this->postJson(route('montonio.webhook'), ['orderToken' => 'some-token'])
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessMontonioWebhook::class, fn($job): bool => $job->type === 'payment' && $job->token === 'some-token');
});

it('queues shipping webhook when queue configured', function (): void {
    Bus::fake(ProcessMontonioWebhook::class);
    config()->set('montonio.webhooks.queue', 'webhooks');

    $this->postJson(route('montonio.webhook'), ['payload' => 'some-shipping-token'])
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessMontonioWebhook::class, fn($job): bool => $job->type === 'shipping' && $job->token === 'some-shipping-token');
});

it('dispatches to configured queue name', function (): void {
    Bus::fake(ProcessMontonioWebhook::class);
    config()->set('montonio.webhooks.queue', 'custom-queue');

    $this->postJson(route('montonio.webhook'), ['orderToken' => 'token']);

    Bus::assertDispatched(ProcessMontonioWebhook::class, fn($job): bool => $job->queue === 'custom-queue');
});

it('prioritizes orderToken over payload', function (): void {
    Event::fake(PaymentWebhookReceived::class);

    $token = encodePaymentToken();

    $this->postJson(route('montonio.webhook'), [
        'orderToken' => $token,
        'payload' => 'should-be-ignored',
    ])->assertOk();

    Event::assertDispatched(PaymentWebhookReceived::class);
});

<?php

declare(strict_types=1);

use Veltix\Montonio\Montonio;

use function Veltix\LaravelMontonio\Tests\fixture;
use function Veltix\LaravelMontonio\Tests\jsonResponse;
use function Veltix\LaravelMontonio\Tests\montonioWithResponses;

it('syncs both payment and shipping methods by default', function (): void {
    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, fixture('Payments/payment-methods.json')),
        jsonResponse(200, fixture('Shipping/shipping-methods.json')),
    ));

    $this->artisan('montonio:sync-methods')
        ->assertSuccessful()
        ->expectsOutputToContain('Payment methods:')
        ->expectsOutputToContain('Shipping methods:');
});

it('syncs only payments with --payments-only', function (): void {
    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, fixture('Payments/payment-methods.json')),
    ));

    $this->artisan('montonio:sync-methods --payments-only')
        ->assertSuccessful()
        ->expectsOutputToContain('Payment methods:')
        ->doesntExpectOutputToContain('Shipping methods:');
});

it('syncs only shipping with --shipping-only', function (): void {
    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, fixture('Shipping/shipping-methods.json')),
    ));

    $this->artisan('montonio:sync-methods --shipping-only')
        ->assertSuccessful()
        ->doesntExpectOutputToContain('Payment methods:')
        ->expectsOutputToContain('Shipping methods:');
});

it('outputs sync counts', function (): void {
    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, fixture('Payments/payment-methods.json')),
        jsonResponse(200, fixture('Shipping/shipping-methods.json')),
    ));

    $this->artisan('montonio:sync-methods')
        ->expectsOutputToContain('10 created')
        ->expectsOutputToContain('4 created');
});

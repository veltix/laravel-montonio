<?php

declare(strict_types=1);

use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Models\ShippingMethod;
use Veltix\LaravelMontonio\Support\ModelResolver;

it('resolves default payment method class', function (): void {
    expect(ModelResolver::paymentMethodClass())->toBe(PaymentMethod::class);
});

it('resolves default shipping method class', function (): void {
    expect(ModelResolver::shippingMethodClass())->toBe(ShippingMethod::class);
});

it('resolves custom payment method class from config', function (): void {
    config()->set('montonio.models.payment_method', 'App\\Models\\CustomPaymentMethod');

    expect(ModelResolver::paymentMethodClass())->toBe('App\\Models\\CustomPaymentMethod');
});

it('resolves custom shipping method class from config', function (): void {
    config()->set('montonio.models.shipping_method', 'App\\Models\\CustomShippingMethod');

    expect(ModelResolver::shippingMethodClass())->toBe('App\\Models\\CustomShippingMethod');
});

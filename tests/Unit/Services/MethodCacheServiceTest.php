<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Models\ShippingMethod;
use Veltix\LaravelMontonio\Services\MethodCacheService;

beforeEach(function (): void {
    $this->service = new MethodCacheService();
});

it('returns active payment methods', function (): void {
    PaymentMethod::create(['method_code' => 'blik', 'name' => 'Blik', 'metadata' => [], 'active' => true]);
    PaymentMethod::create(['method_code' => 'card', 'name' => 'Card', 'metadata' => [], 'active' => false]);

    $result = $this->service->paymentMethods();

    expect($result)->toHaveCount(1)
        ->and($result->first()->method_code)->toBe('blik');
});

it('returns active shipping methods', function (): void {
    ShippingMethod::create(['carrier_code' => 'dpd', 'method_code' => 'courier', 'country' => 'EE', 'name' => 'Courier', 'metadata' => [], 'active' => true]);
    ShippingMethod::create(['carrier_code' => 'dpd', 'method_code' => 'pickupPoint', 'country' => 'EE', 'name' => 'Pickup', 'metadata' => [], 'active' => false]);

    $result = $this->service->shippingMethods();

    expect($result)->toHaveCount(1)
        ->and($result->first()->method_code)->toBe('courier');
});

it('caches results when enabled', function (): void {
    config()->set('montonio.cache.enabled', true);

    PaymentMethod::create(['method_code' => 'blik', 'name' => 'Blik', 'metadata' => [], 'active' => true]);

    $this->service->paymentMethods();

    expect(Cache::has('montonio:payment_methods'))->toBeTrue();
});

it('bypasses cache when disabled', function (): void {
    config()->set('montonio.cache.enabled', false);

    PaymentMethod::create(['method_code' => 'blik', 'name' => 'Blik', 'metadata' => [], 'active' => true]);

    $this->service->paymentMethods();

    expect(Cache::has('montonio:payment_methods'))->toBeFalse();
});

it('uses configured TTL', function (): void {
    config()->set('montonio.cache.enabled', true);
    config()->set('montonio.cache.ttl', 600);

    PaymentMethod::create(['method_code' => 'blik', 'name' => 'Blik', 'metadata' => [], 'active' => true]);

    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(fn ($key, $ttl): bool => $key === 'montonio:payment_methods' && $ttl === 600)
        ->andReturn(PaymentMethod::active()->get());

    $this->service->paymentMethods();
});

it('returns empty collection when no records exist', function (): void {
    expect($this->service->paymentMethods())->toBeEmpty()
        ->and($this->service->shippingMethods())->toBeEmpty();
});

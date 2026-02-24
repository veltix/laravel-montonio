<?php

declare(strict_types=1);

use Veltix\LaravelMontonio\Models\ShippingMethod;

it('uses the correct table name', function (): void {
    expect((new ShippingMethod())->getTable())->toBe('montonio_shipping_methods');
});

it('casts metadata to array and active to boolean', function (): void {
    $model = ShippingMethod::create([
        'carrier_code' => 'dpd',
        'method_code' => 'courier',
        'country' => 'EE',
        'name' => 'Courier',
        'metadata' => ['subtypes' => [['code' => 'standard']]],
        'active' => 1,
    ]);

    $model->refresh();

    expect($model->metadata)->toBeArray()
        ->and($model->metadata['subtypes'])->toBeArray()
        ->and($model->active)->toBeBool()
        ->and($model->active)->toBeTrue();
});

it('filters inactive records with scopeActive', function (): void {
    ShippingMethod::create(['carrier_code' => 'dpd', 'method_code' => 'courier', 'country' => 'EE', 'name' => 'Courier', 'metadata' => [], 'active' => true]);
    ShippingMethod::create(['carrier_code' => 'dpd', 'method_code' => 'pickupPoint', 'country' => 'EE', 'name' => 'Pickup', 'metadata' => [], 'active' => false]);

    $active = ShippingMethod::active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->method_code)->toBe('courier');
});

it('is mass assignable', function (): void {
    $model = ShippingMethod::create([
        'carrier_code' => 'dpd',
        'method_code' => 'courier',
        'country' => 'EE',
        'name' => 'Courier',
        'metadata' => ['subtypes' => null, 'constraints' => null],
        'active' => true,
    ]);

    expect($model->exists)->toBeTrue()
        ->and($model->carrier_code)->toBe('dpd')
        ->and($model->method_code)->toBe('courier')
        ->and($model->country)->toBe('EE');
});

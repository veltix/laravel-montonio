<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Veltix\LaravelMontonio\Models\ShippingMethod;
use Veltix\LaravelMontonio\Support\SyncResult;
use Veltix\Montonio\Montonio;

use function Veltix\LaravelMontonio\Tests\fixture;
use function Veltix\LaravelMontonio\Tests\jsonResponse;
use function Veltix\LaravelMontonio\Tests\montonioWithResponses;

beforeEach(function (): void {
    $montonio = montonioWithResponses(
        jsonResponse(200, fixture('Shipping/shipping-methods.json')),
    );
    $this->app->instance(Montonio::class, $montonio);
});

it('creates correct records from fixture', function (): void {
    $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    // EE: dpd/courier, dpd/pickupPoint, omniva/courier, omniva/pickupPoint = 4
    expect(ShippingMethod::count())->toBe(4);
});

it('returns correct SyncResult', function (): void {
    $result = $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    expect($result)->toBeInstanceOf(SyncResult::class)
        ->and($result->created)->toBe(4)
        ->and($result->updated)->toBe(0)
        ->and($result->deactivated)->toBe(0);
});

it('updates on second sync', function (): void {
    $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, fixture('Shipping/shipping-methods.json')),
    ));

    $result = $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(4)
        ->and($result->deactivated)->toBe(0);
});

it('deactivates stale records', function (): void {
    ShippingMethod::create([
        'carrier_code' => 'obsolete',
        'method_code' => 'courier',
        'country' => 'EE',
        'name' => 'Obsolete',
        'metadata' => [],
        'active' => true,
    ]);

    $result = $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    expect($result->deactivated)->toBe(1)
        ->and(ShippingMethod::where('carrier_code', 'obsolete')->first()->active)->toBeFalse();
});

it('flushes shipping methods cache key', function (): void {
    Cache::put('montonio:shipping_methods', 'cached-value', 3600);

    $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    expect(Cache::has('montonio:shipping_methods'))->toBeFalse();
});

it('stores subtypes and constraints in metadata', function (): void {
    $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    $dpdCourier = ShippingMethod::where('carrier_code', 'dpd')->where('method_code', 'courier')->first();
    expect($dpdCourier->metadata['subtypes'])->toBe([['code' => 'standard', 'rate' => null, 'currency' => null]])
        ->and($dpdCourier->metadata['constraints'])->toBe(['parcel_dimensions_required' => false]);

    $dpdPickup = ShippingMethod::where('carrier_code', 'dpd')->where('method_code', 'pickupPoint')->first();
    expect($dpdPickup->metadata['subtypes'])->toHaveCount(2)
        ->and($dpdPickup->metadata['subtypes'][0]['code'])->toBe('parcelMachine')
        ->and($dpdPickup->metadata['subtypes'][1]['code'])->toBe('parcelShop');
});

it('stores null subtypes and constraints when absent', function (): void {
    // Create a fixture with null subtypes/constraints
    $fixtureData = [
        'countries' => [
            [
                'countryCode' => 'LT',
                'carriers' => [
                    [
                        'carrierCode' => 'testCarrier',
                        'shippingMethods' => [
                            ['type' => 'courier'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, $fixtureData),
    ));

    $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    $method = ShippingMethod::where('carrier_code', 'testCarrier')->first();
    expect($method->metadata['subtypes'])->toBeNull()
        ->and($method->metadata['constraints'])->toBeNull();
});

it('generates name via Str::headline', function (): void {
    $this->app->make(\Veltix\LaravelMontonio\Actions\SyncShippingMethods::class)->handle();

    expect(ShippingMethod::where('method_code', 'pickupPoint')->first()->name)->toBe('Pickup Point')
        ->and(ShippingMethod::where('method_code', 'courier')->first()->name)->toBe('Courier');
});

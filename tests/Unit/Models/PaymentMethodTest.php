<?php

declare(strict_types=1);

use Veltix\LaravelMontonio\Models\PaymentMethod;

it('uses the correct table name', function (): void {
    expect((new PaymentMethod())->getTable())->toBe('montonio_payment_methods');
});

it('casts metadata to array and active to boolean', function (): void {
    $model = PaymentMethod::create([
        'method_code' => 'blik',
        'name' => 'Blik',
        'metadata' => ['processor' => 'stripe'],
        'active' => 1,
    ]);

    $model->refresh();

    expect($model->metadata)->toBeArray()
        ->and($model->metadata['processor'])->toBe('stripe')
        ->and($model->active)->toBeBool()
        ->and($model->active)->toBeTrue();
});

it('filters inactive records with scopeActive', function (): void {
    PaymentMethod::create(['method_code' => 'blik', 'name' => 'Blik', 'metadata' => [], 'active' => true]);
    PaymentMethod::create(['method_code' => 'card', 'name' => 'Card', 'metadata' => [], 'active' => false]);

    $active = PaymentMethod::active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->method_code)->toBe('blik');
});

it('is mass assignable', function (): void {
    $model = PaymentMethod::create([
        'method_code' => 'blik',
        'name' => 'Blik',
        'country' => 'PL',
        'currency' => 'PLN',
        'metadata' => ['processor' => 'stripe'],
        'active' => true,
    ]);

    expect($model->exists)->toBeTrue()
        ->and($model->method_code)->toBe('blik')
        ->and($model->country)->toBe('PL')
        ->and($model->currency)->toBe('PLN');
});

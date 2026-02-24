<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Support\SyncResult;
use Veltix\Montonio\Montonio;

use function Veltix\LaravelMontonio\Tests\fixture;
use function Veltix\LaravelMontonio\Tests\jsonResponse;
use function Veltix\LaravelMontonio\Tests\montonioWithResponses;

beforeEach(function (): void {
    $montonio = montonioWithResponses(
        jsonResponse(200, fixture('Payments/payment-methods.json')),
    );
    $this->app->instance(Montonio::class, $montonio);
});

it('creates correct number of records from fixture', function (): void {
    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    // blik (no setup) = 1 row
    // cardPayments (no setup) = 1 row
    // bnpl (no setup) = 1 row
    // hirePurchase (no setup) = 1 row
    // paymentInitiation (setup with PL, FI, DE, LT, EE, LV) = 6 rows
    expect(PaymentMethod::count())->toBe(10);
});

it('returns SyncResult with correct created count on fresh sync', function (): void {
    $result = $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    expect($result)->toBeInstanceOf(SyncResult::class)
        ->and($result->created)->toBe(10)
        ->and($result->updated)->toBe(0)
        ->and($result->deactivated)->toBe(0);
});

it('updates on second sync', function (): void {
    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    // Re-bind with fresh response for second sync
    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(200, fixture('Payments/payment-methods.json')),
    ));

    $result = $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(10)
        ->and($result->deactivated)->toBe(0);
});

it('deactivates stale records not in response', function (): void {
    PaymentMethod::create([
        'method_code' => 'obsoleteMethod',
        'name' => 'Obsolete',
        'country' => null,
        'metadata' => [],
        'active' => true,
    ]);

    $result = $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    expect($result->deactivated)->toBe(1)
        ->and(PaymentMethod::where('method_code', 'obsoleteMethod')->first()->active)->toBeFalse();
});

it('flushes payment methods cache key', function (): void {
    Cache::put('montonio:payment_methods', 'cached-value', 3600);

    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    expect(Cache::has('montonio:payment_methods'))->toBeFalse();
});

it('stores null country for methods without setup', function (): void {
    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    $blik = PaymentMethod::where('method_code', 'blik')->first();

    expect($blik->country)->toBeNull();
});

it('stores metadata with processor and logo_url', function (): void {
    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    $blik = PaymentMethod::where('method_code', 'blik')->first();
    expect($blik->metadata['processor'])->toBe('stripe')
        ->and($blik->metadata['logo_url'])->toBe('https://public.montonio.com/images/logos/blik.png');

    $pi = PaymentMethod::where('method_code', 'paymentInitiation')->where('country', 'PL')->first();
    expect($pi->metadata['processor'])->toBe('montonio')
        ->and($pi->metadata['supported_currencies'])->toBe(['EUR', 'PLN'])
        ->and($pi->metadata['banks'])->toBeArray()
        ->and($pi->metadata['banks'])->toHaveCount(12);
});

it('generates name via Str::headline', function (): void {
    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    expect(PaymentMethod::where('method_code', 'paymentInitiation')->first()->name)->toBe('Payment Initiation')
        ->and(PaymentMethod::where('method_code', 'cardPayments')->first()->name)->toBe('Card Payments')
        ->and(PaymentMethod::where('method_code', 'hirePurchase')->first()->name)->toBe('Hire Purchase');
});

it('sets currency from first supported currency', function (): void {
    $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();

    $pl = PaymentMethod::where('method_code', 'paymentInitiation')->where('country', 'PL')->first();
    expect($pl->currency)->toBe('EUR'); // first in ['EUR', 'PLN']

    $blik = PaymentMethod::where('method_code', 'blik')->first();
    expect($blik->currency)->toBeNull(); // no setup, no currency
});

it('rolls back on exception', function (): void {
    // First, create a record so we can verify it's untouched after rollback
    PaymentMethod::create([
        'method_code' => 'existing',
        'name' => 'Existing',
        'metadata' => [],
        'active' => true,
    ]);

    // Bind a Montonio that will fail mid-transaction
    $this->app->instance(Montonio::class, montonioWithResponses(
        jsonResponse(500, ['error' => 'Server Error']),
    ));

    try {
        $this->app->make(Veltix\LaravelMontonio\Actions\SyncPaymentMethods::class)->handle();
    } catch (Throwable) {
    }

    // Original record should still be active (transaction rolled back)
    expect(PaymentMethod::where('method_code', 'existing')->first()->active)->toBeTrue()
        ->and(PaymentMethod::count())->toBe(1);
});

<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Models\ShippingMethod;

final class MethodCacheService
{
    public function paymentMethods(): Collection
    {
        if (! config('montonio.cache.enabled')) {
            return PaymentMethod::active()->get();
        }

        return Cache::remember(
            'montonio:payment_methods',
            config('montonio.cache.ttl'),
            fn () => PaymentMethod::active()->get(),
        );
    }

    public function shippingMethods(): Collection
    {
        if (! config('montonio.cache.enabled')) {
            return ShippingMethod::active()->get();
        }

        return Cache::remember(
            'montonio:shipping_methods',
            config('montonio.cache.ttl'),
            fn () => ShippingMethod::active()->get(),
        );
    }
}

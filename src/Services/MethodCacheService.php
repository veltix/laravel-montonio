<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Models\ShippingMethod;
use Veltix\LaravelMontonio\Support\ModelResolver;

final class MethodCacheService
{
    /** @return Collection<int, PaymentMethod> */
    public function paymentMethods(): Collection
    {
        $model = ModelResolver::paymentMethodClass();

        if (! config('montonio.cache.enabled')) {
            return $model::active()->get();
        }

        /** @var int $ttl */
        $ttl = config('montonio.cache.ttl');

        return Cache::remember(
            'montonio:payment_methods',
            $ttl,
            fn () => $model::active()->get(),
        );
    }

    /** @return Collection<int, ShippingMethod> */
    public function shippingMethods(): Collection
    {
        $model = ModelResolver::shippingMethodClass();

        if (! config('montonio.cache.enabled')) {
            return $model::active()->get();
        }

        /** @var int $ttl */
        $ttl = config('montonio.cache.ttl');

        return Cache::remember(
            'montonio:shipping_methods',
            $ttl,
            fn () => $model::active()->get(),
        );
    }
}

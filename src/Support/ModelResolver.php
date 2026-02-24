<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Support;

use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Models\ShippingMethod;

final class ModelResolver
{
    /** @return class-string<PaymentMethod> */
    public static function paymentMethodClass(): string
    {
        /** @var class-string<PaymentMethod> */
        return config('montonio.models.payment_method');
    }

    /** @return class-string<ShippingMethod> */
    public static function shippingMethodClass(): string
    {
        /** @var class-string<ShippingMethod> */
        return config('montonio.models.shipping_method');
    }
}

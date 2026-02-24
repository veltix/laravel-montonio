<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Facades;

use Illuminate\Support\Facades\Facade;
use Veltix\Montonio\Payments\PaymentsClient;
use Veltix\Montonio\Shipping\ShippingClient;
use Veltix\Montonio\Webhook\WebhookVerifier;

/**
 * @method static PaymentsClient payments()
 * @method static ShippingClient shipping()
 * @method static WebhookVerifier webhooks()
 *
 * @see \Veltix\Montonio\Montonio
 */
final class Montonio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Veltix\Montonio\Montonio::class;
    }
}

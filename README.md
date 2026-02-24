# Laravel Montonio

Laravel integration for the [Montonio](https://montonio.com) Payments and Shipping APIs.

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

## Installation

```bash
composer require veltix/laravel-montonio
```

Run the install command to publish the config and migrations:

```bash
php artisan montonio:install
```

Add your API credentials to `.env`:

```dotenv
MONTONIO_ACCESS_KEY=your-access-key
MONTONIO_SECRET_KEY=your-secret-key
MONTONIO_ENVIRONMENT=sandbox
```

Run the migrations and sync methods from the Montonio API:

```bash
php artisan migrate
php artisan montonio:sync-methods
```

## Configuration

Publish the config file manually (the install command does this automatically):

```bash
php artisan vendor:publish --tag=montonio-config
```

### Environment variables

| Variable | Default | Description |
|---|---|---|
| `MONTONIO_ACCESS_KEY` | `''` | Your Montonio API access key |
| `MONTONIO_SECRET_KEY` | `''` | Your Montonio API secret key |
| `MONTONIO_ENVIRONMENT` | `sandbox` | API environment (`sandbox` or `production`) |
| `MONTONIO_TIMEOUT` | `30` | HTTP request timeout in seconds |
| `MONTONIO_CACHE_ENABLED` | `true` | Enable caching for payment/shipping methods |
| `MONTONIO_CACHE_TTL` | `3600` | Cache time-to-live in seconds |
| `MONTONIO_WEBHOOK_ROUTE` | `/montonio/webhook` | Webhook endpoint path |
| `MONTONIO_WEBHOOK_QUEUE` | `null` | Queue name for async webhook processing |

## Usage

### Facade

Access the Montonio API clients through the `Montonio` facade:

```php
use Veltix\LaravelMontonio\Facades\Montonio;

// Payments API
$paymentsClient = Montonio::payments();

// Shipping API
$shippingClient = Montonio::shipping();

// Webhook verifier
$webhookVerifier = Montonio::webhooks();
```

### Syncing methods

Sync payment and shipping methods from the Montonio API:

```bash
# Sync both payment and shipping methods
php artisan montonio:sync-methods

# Sync only payment methods
php artisan montonio:sync-methods --payments-only

# Sync only shipping methods
php artisan montonio:sync-methods --shipping-only
```

### Querying methods

Use the `PaymentMethod` and `ShippingMethod` Eloquent models to query synced methods:

```php
use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Models\ShippingMethod;

// Get all active payment methods
$methods = PaymentMethod::active()->get();

// Get all active shipping methods
$methods = ShippingMethod::active()->get();
```

For cached queries, use the `MethodCacheService`:

```php
use Veltix\LaravelMontonio\Services\MethodCacheService;

$cache = app(MethodCacheService::class);

$paymentMethods = $cache->paymentMethods();
$shippingMethods = $cache->shippingMethods();
```

## Webhooks

The package automatically registers a `POST` route at `/montonio/webhook` (configurable via `MONTONIO_WEBHOOK_ROUTE`). The controller detects whether the incoming webhook is a payment or shipping notification and dispatches the appropriate event.

### Events

**Payment webhook:**

| Event | Payload |
|---|---|
| `PaymentWebhookReceived` | `PaymentWebhookPayload` |

**Shipping webhooks:**

| Event | Payload |
|---|---|
| `ShipmentRegistered` | `ShippingWebhookPayload` |
| `ShipmentRegistrationFailed` | `ShippingWebhookPayload` |
| `ShipmentStatusUpdated` | `ShippingWebhookPayload` |
| `ShipmentLabelsCreated` | `ShippingWebhookPayload` |
| `LabelFileReady` | `ShippingWebhookPayload` |
| `LabelFileCreationFailed` | `ShippingWebhookPayload` |

All events live in the `Veltix\LaravelMontonio\Events` namespace.

### Listening to events

Register listeners in your `EventServiceProvider` or use Laravel's event discovery:

```php
use Veltix\LaravelMontonio\Events\PaymentWebhookReceived;
use Veltix\LaravelMontonio\Events\ShipmentStatusUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentWebhookReceived::class => [
            HandlePaymentWebhook::class,
        ],
        ShipmentStatusUpdated::class => [
            HandleShipmentUpdate::class,
        ],
    ];
}
```

Each event has a `public PaymentWebhookPayload|ShippingWebhookPayload $payload` property containing the verified webhook data.

### Queued webhooks

To process webhooks asynchronously, set the queue name:

```dotenv
MONTONIO_WEBHOOK_QUEUE=montonio
```

When set, the controller immediately responds with `202` and dispatches a `ProcessMontonioWebhook` job onto the specified queue. The job verifies the token and fires the appropriate event.

### Webhook middleware

Add middleware to the webhook route via the config:

```php
// config/montonio.php
'webhooks' => [
    'middleware' => ['api', App\Http\Middleware\VerifyIpWhitelist::class],
],
```

## Caching

The `MethodCacheService` caches active payment and shipping methods using Laravel's cache system. Configure caching with:

```dotenv
MONTONIO_CACHE_ENABLED=true
MONTONIO_CACHE_TTL=3600
```

Set `MONTONIO_CACHE_ENABLED=false` to disable caching and query the database directly.

Cache keys used: `montonio:payment_methods` and `montonio:shipping_methods`.

## Custom HTTP client

The package uses [Guzzle](https://docs.guzzlephp.org) by default. To use a custom PSR-18 HTTP client, bind the PSR interfaces in a service provider:

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

$this->app->bind(ClientInterface::class, fn () => new MyCustomClient());
$this->app->bind(RequestFactoryInterface::class, fn () => new MyRequestFactory());
$this->app->bind(StreamFactoryInterface::class, fn () => new MyStreamFactory());
```

## Testing

```bash
composer test
```

## License

MIT

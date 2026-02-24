<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Veltix\LaravelMontonio\Support\ConfigFactory;
use Veltix\Montonio\Config;
use Veltix\Montonio\Environment;

it('creates config with Guzzle fallback when no PSR bindings exist', function (): void {
    $config = ConfigFactory::make();

    expect($config)->toBeInstanceOf(Config::class)
        ->and($config->accessKey)->toBe('test_access_key')
        ->and($config->secretKey)->toBe('test_secret_key_long_enough_for_hmac256')
        ->and($config->environment)->toBe(Environment::Sandbox);
});

it('uses bound ClientInterface from container', function (): void {
    $fakeClient = new Client(['timeout' => 10]);
    $this->app->instance(ClientInterface::class, $fakeClient);

    $config = ConfigFactory::make();

    expect($config->httpClient)->toBe($fakeClient);
});

it('uses bound RequestFactoryInterface from container', function (): void {
    $factory = new HttpFactory();
    $this->app->instance(RequestFactoryInterface::class, $factory);

    $config = ConfigFactory::make();

    expect($config->requestFactory)->toBe($factory);
});

it('uses bound StreamFactoryInterface from container', function (): void {
    $factory = new HttpFactory();
    $this->app->instance(StreamFactoryInterface::class, $factory);

    $config = ConfigFactory::make();

    expect($config->streamFactory)->toBe($factory);
});

it('maps environment string to Environment enum', function (): void {
    config()->set('montonio.environment', 'production');

    $config = ConfigFactory::make();

    expect($config->environment)->toBe(Environment::Production);
});

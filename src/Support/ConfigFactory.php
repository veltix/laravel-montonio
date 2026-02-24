<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Veltix\Montonio\Config;
use Veltix\Montonio\Environment;

final class ConfigFactory
{
    public static function make(): Config
    {
        $app = app();

        $httpClient = $app->bound(ClientInterface::class)
            ? $app->make(ClientInterface::class)
            : new Client(['timeout' => config('montonio.timeout')]);

        $requestFactory = $app->bound(RequestFactoryInterface::class)
            ? $app->make(RequestFactoryInterface::class)
            : new HttpFactory();

        $streamFactory = $app->bound(StreamFactoryInterface::class)
            ? $app->make(StreamFactoryInterface::class)
            : new HttpFactory();

        return new Config(
            accessKey: config('montonio.access_key'),
            secretKey: config('montonio.secret_key'),
            environment: Environment::from(config('montonio.environment')),
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );
    }
}

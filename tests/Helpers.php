<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Tests;

use Closure;
use Firebase\JWT\JWT;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Veltix\Montonio\Config;
use Veltix\Montonio\Environment;
use Veltix\Montonio\Montonio;
use Veltix\Montonio\Shipping\Enum\ShippingWebhookEvent;

function streamFromString(string $content): StreamInterface
{
    $resource = fopen('php://temp', 'r+');
    fwrite($resource, $content);
    rewind($resource);

    return new class($resource, $content) implements StreamInterface
    {
        public function __construct(private $resource, private readonly string $content) {}

        public function __toString(): string
        {
            return $this->content;
        }

        public function close(): void
        {
            if (is_resource($this->resource)) {
                fclose($this->resource);
            }
        }

        public function detach()
        {
            $r = $this->resource;
            $this->resource = null;

            return $r;
        }

        public function getSize(): int
        {
            return mb_strlen($this->content);
        }

        public function tell(): int
        {
            return ftell($this->resource);
        }

        public function eof(): bool
        {
            return feof($this->resource);
        }

        public function isSeekable(): bool
        {
            return true;
        }

        public function seek(int $offset, int $whence = SEEK_SET): void
        {
            fseek($this->resource, $offset, $whence);
        }

        public function rewind(): void
        {
            rewind($this->resource);
        }

        public function isWritable(): bool
        {
            return true;
        }

        public function write(string $string): int
        {
            return fwrite($this->resource, $string);
        }

        public function isReadable(): bool
        {
            return true;
        }

        public function read(int $length): string
        {
            return fread($this->resource, $length);
        }

        public function getContents(): string
        {
            return stream_get_contents($this->resource);
        }

        public function getMetadata(?string $key = null)
        {
            return $key ? null : [];
        }
    };
}

function psrRequest(string $method = 'GET', string $url = ''): RequestInterface
{
    return new class($method, $url) implements RequestInterface
    {
        private array $headers = [];

        private ?StreamInterface $body = null;

        private string $protocolVersion = '1.1';

        public function __construct(private string $method, private string $url) {}

        public function getRequestTarget(): string
        {
            return parse_url($this->url, PHP_URL_PATH) ?? '/';
        }

        public function withRequestTarget(string $requestTarget): RequestInterface
        {
            return $this;
        }

        public function getMethod(): string
        {
            return $this->method;
        }

        public function withMethod(string $method): RequestInterface
        {
            $c = clone $this;
            $c->method = $method;

            return $c;
        }

        public function getUri(): UriInterface
        {
            return new readonly class($this->url) implements UriInterface
            {
                public function __construct(private string $url) {}

                public function getScheme(): string
                {
                    return parse_url($this->url, PHP_URL_SCHEME) ?? '';
                }

                public function getAuthority(): string
                {
                    return parse_url($this->url, PHP_URL_HOST) ?? '';
                }

                public function getUserInfo(): string
                {
                    return '';
                }

                public function getHost(): string
                {
                    return parse_url($this->url, PHP_URL_HOST) ?? '';
                }

                public function getPort(): ?int
                {
                    return parse_url($this->url, PHP_URL_PORT) ?: null;
                }

                public function getPath(): string
                {
                    return parse_url($this->url, PHP_URL_PATH) ?? '';
                }

                public function getQuery(): string
                {
                    return parse_url($this->url, PHP_URL_QUERY) ?? '';
                }

                public function getFragment(): string
                {
                    return parse_url($this->url, PHP_URL_FRAGMENT) ?? '';
                }

                public function withScheme(string $scheme): UriInterface
                {
                    return $this;
                }

                public function withUserInfo(string $user, ?string $password = null): UriInterface
                {
                    return $this;
                }

                public function withHost(string $host): UriInterface
                {
                    return $this;
                }

                public function withPort(?int $port): UriInterface
                {
                    return $this;
                }

                public function withPath(string $path): UriInterface
                {
                    return $this;
                }

                public function withQuery(string $query): UriInterface
                {
                    return $this;
                }

                public function withFragment(string $fragment): UriInterface
                {
                    return $this;
                }

                public function __toString(): string
                {
                    return $this->url;
                }
            };
        }

        public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
        {
            $c = clone $this;
            $c->url = (string) $uri;

            return $c;
        }

        public function getProtocolVersion(): string
        {
            return $this->protocolVersion;
        }

        public function withProtocolVersion(string $version): RequestInterface
        {
            $c = clone $this;
            $c->protocolVersion = $version;

            return $c;
        }

        public function getHeaders(): array
        {
            return $this->headers;
        }

        public function hasHeader(string $name): bool
        {
            return isset($this->headers[mb_strtolower($name)]);
        }

        public function getHeader(string $name): array
        {
            return $this->headers[mb_strtolower($name)] ?? [];
        }

        public function getHeaderLine(string $name): string
        {
            return implode(', ', $this->getHeader($name));
        }

        public function withHeader(string $name, $value): RequestInterface
        {
            $c = clone $this;
            $c->headers[mb_strtolower($name)] = is_array($value) ? $value : [$value];

            return $c;
        }

        public function withAddedHeader(string $name, $value): RequestInterface
        {
            return $this->withHeader($name, $value);
        }

        public function withoutHeader(string $name): RequestInterface
        {
            $c = clone $this;
            unset($c->headers[mb_strtolower($name)]);

            return $c;
        }

        public function getBody(): StreamInterface
        {
            return $this->body ?? streamFromString('');
        }

        public function withBody(StreamInterface $body): RequestInterface
        {
            $c = clone $this;
            $c->body = $body;

            return $c;
        }
    };
}

function jsonResponse(int $status, array $body): ResponseInterface
{
    $json = json_encode($body);

    return new class($status, $json) implements ResponseInterface
    {
        private string $protocolVersion = '1.1';

        private array $headers = ['content-type' => ['application/json']];

        public function __construct(private int $status, private string $json) {}

        public function getStatusCode(): int
        {
            return $this->status;
        }

        public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
        {
            $c = clone $this;
            $c->status = $code;

            return $c;
        }

        public function getReasonPhrase(): string
        {
            return '';
        }

        public function getProtocolVersion(): string
        {
            return $this->protocolVersion;
        }

        public function withProtocolVersion(string $version): ResponseInterface
        {
            $c = clone $this;
            $c->protocolVersion = $version;

            return $c;
        }

        public function getHeaders(): array
        {
            return $this->headers;
        }

        public function hasHeader(string $name): bool
        {
            return isset($this->headers[mb_strtolower($name)]);
        }

        public function getHeader(string $name): array
        {
            return $this->headers[mb_strtolower($name)] ?? [];
        }

        public function getHeaderLine(string $name): string
        {
            return implode(', ', $this->getHeader($name));
        }

        public function withHeader(string $name, $value): ResponseInterface
        {
            $c = clone $this;
            $c->headers[mb_strtolower($name)] = is_array($value) ? $value : [$value];

            return $c;
        }

        public function withAddedHeader(string $name, $value): ResponseInterface
        {
            return $this->withHeader($name, $value);
        }

        public function withoutHeader(string $name): ResponseInterface
        {
            $c = clone $this;
            unset($c->headers[mb_strtolower($name)]);

            return $c;
        }

        public function getBody(): StreamInterface
        {
            return streamFromString($this->json);
        }

        public function withBody(StreamInterface $body): ResponseInterface
        {
            return $this;
        }
    };
}

/**
 * @return object{client: ClientInterface, lastRequest: Closure(): ?RequestInterface}
 */
function fakeClient(ResponseInterface ...$responses): object
{
    $queue = $responses;
    $lastRequest = null;

    $client = new class($queue, $lastRequest) implements ClientInterface
    {
        public function __construct(private array &$queue, private ?RequestInterface &$lastRequest) {}

        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $this->lastRequest = $request;
            if ($this->queue === []) {
                throw new class('No more responses in queue') extends RuntimeException implements ClientExceptionInterface {};
            }

            return array_shift($this->queue);
        }
    };

    return new class($client, $lastRequest)
    {
        private ?RequestInterface $lastRequest = null;

        public function __construct(public ClientInterface $client, ?RequestInterface &$lastRequest)
        {
            $this->lastRequest = &$lastRequest;
        }

        public function lastRequest(): ?RequestInterface
        {
            return $this->lastRequest;
        }
    };
}

function mockRequestFactory(): RequestFactoryInterface
{
    return new class implements RequestFactoryInterface
    {
        public function createRequest(string $method, $uri): RequestInterface
        {
            return psrRequest($method, (string) $uri);
        }
    };
}

function mockStreamFactory(): StreamFactoryInterface
{
    return new class implements StreamFactoryInterface
    {
        public function createStream(string $content = ''): StreamInterface
        {
            return streamFromString($content);
        }

        public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
        {
            return streamFromString(file_get_contents($filename));
        }

        public function createStreamFromResource($resource): StreamInterface
        {
            return streamFromString(stream_get_contents($resource));
        }
    };
}

function testConfig(?ClientInterface $httpClient = null): Config
{
    $mock = $httpClient instanceof ClientInterface ? null : fakeClient(jsonResponse(200, []));

    return new Config(
        accessKey: 'test_access_key',
        secretKey: 'test_secret_key_long_enough_for_hmac256',
        environment: Environment::Sandbox,
        httpClient: $httpClient ?? $mock->client,
        requestFactory: mockRequestFactory(),
        streamFactory: mockStreamFactory(),
    );
}

function montonioWithResponses(ResponseInterface ...$responses): Montonio
{
    $fake = fakeClient(...$responses);

    return new Montonio(new Config(
        accessKey: 'test_access_key',
        secretKey: 'test_secret_key_long_enough_for_hmac256',
        environment: Environment::Sandbox,
        httpClient: $fake->client,
        requestFactory: mockRequestFactory(),
        streamFactory: mockStreamFactory(),
    ));
}

function encodePaymentToken(array $overrides = []): string
{
    $payload = array_merge(fixture('Payments/webhook-payload.json'), [
        'iat' => time(),
        'exp' => time() + 3600,
    ], $overrides);

    return JWT::encode($payload, 'test_secret_key_long_enough_for_hmac256', 'HS256');
}

function encodeShippingToken(ShippingWebhookEvent $event, array $overrides = []): string
{
    $payload = array_merge(fixture('Shipping/webhook-payload.json'), [
        'eventType' => $event->value,
        'iat' => time(),
        'exp' => time() + 3600,
    ], $overrides);

    // Ensure data is an object for JWT encoding
    if (isset($payload['data']) && is_array($payload['data'])) {
        $payload['data'] = (object) $payload['data'];
    }

    return JWT::encode($payload, 'test_secret_key_long_enough_for_hmac256', 'HS256');
}

function fixture(string $path): array
{
    $file = __DIR__.'/Fixtures/'.$path;

    return json_decode(file_get_contents($file), true);
}

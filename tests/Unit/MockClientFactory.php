<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Paybeta\PaybetaClient;

trait MockClientFactory
{
    private array $requestHistory = [];

    /**
     * @param array<int, array{status: int, body: array<mixed>}> $responses
     */
    private function makeClient(array $responses, ?string $webhookSecret = null): PaybetaClient
    {
        $mock = new MockHandler(array_map(
            static fn (array $r) => new Response($r['status'], ['Content-Type' => 'application/json'], json_encode($r['body'])),
            $responses,
        ));

        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($this->requestHistory));

        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        return new PaybetaClient(
            apiKey: 'pb_test_fake',
            webhookSecret: $webhookSecret,
            httpClient: $guzzle,
        );
    }

    private function envelope(mixed $data): array
    {
        return ['status' => 'success', 'data' => $data, 'timestamp' => '2026-01-01T00:00:00.000Z'];
    }
}

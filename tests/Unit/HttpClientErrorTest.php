<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use Paybeta\Exceptions\PaybetaApiException;
use PHPUnit\Framework\TestCase;

class HttpClientErrorTest extends TestCase
{
    use MockClientFactory;

    public function testNonSuccessResponseThrowsPaybetaApiException(): void
    {
        $client = $this->makeClient([
            ['status' => 404, 'body' => [
                'status' => 'error',
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Payment link not found.',
                    'traceId' => 'trace-123',
                    'timestamp' => '2026-01-01T00:00:00.000Z',
                ],
                'timestamp' => '2026-01-01T00:00:00.000Z',
            ]],
        ]);

        try {
            $client->paymentLinks()->retrieve('missing-token');
            self::fail('Expected PaybetaApiException to be thrown');
        } catch (PaybetaApiException $e) {
            self::assertSame(404, $e->status);
            self::assertSame('NOT_FOUND', $e->errorCode);
            self::assertSame('Payment link not found.', $e->getMessage());
            self::assertSame('trace-123', $e->traceId);
        }
    }
}

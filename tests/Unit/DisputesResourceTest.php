<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DisputesResourceTest extends TestCase
{
    use MockClientFactory;

    public function testOpenSendsAllRequiredFields(): void
    {
        $client = $this->makeClient([
            ['status' => 201, 'body' => $this->envelope(['id' => 'dp-1', 'status' => 'OPENED'])],
        ]);

        $params = [
            'transactionId' => 'txn-1',
            'escrowId' => 'esc-1',
            'merchantId' => 'm1',
            'buyerEmail' => 'buyer@example.com',
            'sellerEmail' => 'seller@example.com',
            'disputeType' => 'NON_DELIVERY',
            'priority' => 'HIGH',
            'description' => 'Item not delivered',
            'amount' => 150000,
            'currency' => 'NGN',
            'openedBy' => 'BUYER',
        ];

        $result = $client->disputes()->open($params);

        self::assertSame('OPENED', $result['status']);
        $body = json_decode((string) $this->requestHistory[0]['request']->getBody(), true);
        self::assertSame($params, $body);
    }

    public function testResolveWithOutcomeAndNotes(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope(['id' => 'dp-1', 'status' => 'RESOLVED'])],
        ]);

        $client->disputes()->resolve('dp-1', ['outcome' => 'BUYER_WINS', 'notes' => 'Confirmed non-delivery.']);

        $request = $this->requestHistory[0]['request'];
        self::assertSame('v1/disputes/dp-1/resolve', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        self::assertSame('BUYER_WINS', $body['outcome']);
    }
}

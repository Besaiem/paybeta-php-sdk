<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use PHPUnit\Framework\TestCase;

class TransactionsResourceTest extends TestCase
{
    use MockClientFactory;

    public function testCreateAttachesIdempotencyKey(): void
    {
        $client = $this->makeClient([
            ['status' => 201, 'body' => $this->envelope(['id' => 'txn-1', 'status' => 'INITIATED'])],
        ]);

        $result = $client->transactions()->create([
            'merchantId' => 'm1',
            'buyerEmail' => 'buyer@example.com',
            'buyerPhone' => '+2348012345678',
            'sellerEmail' => 'seller@example.com',
            'amount' => 1500,
            'currency' => 'NGN',
        ], idempotencyKey: 'idem-1');

        self::assertSame('INITIATED', $result['status']);
        $body = json_decode((string) $this->requestHistory[0]['request']->getBody(), true);
        self::assertSame('idem-1', $body['idempotencyKey']);
    }

    public function testListRoutesToMerchantScopedPathWhenMerchantIdGiven(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope([['id' => 'txn-1']])],
        ]);

        $client->transactions()->list(['merchantId' => 'm1', 'status' => 'FUNDED']);

        $request = $this->requestHistory[0]['request'];
        self::assertSame('v1/transactions/merchant/m1', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('FUNDED', $query['status']);
        self::assertArrayNotHasKey('merchantId', $query);
    }

    public function testListUsesBarePathWhenNoMerchantIdGiven(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope([])],
        ]);

        $client->transactions()->list();

        self::assertSame('v1/transactions', $this->requestHistory[0]['request']->getUri()->getPath());
    }
}

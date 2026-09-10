<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use PHPUnit\Framework\TestCase;

class EscrowsResourceTest extends TestCase
{
    use MockClientFactory;

    public function testListReturnsEnvelopeUnlikeOtherResources(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope([
                'escrows' => [['id' => 'esc-1']],
                'total' => 1,
                'limit' => 20,
                'offset' => 0,
            ])],
        ]);

        $result = $client->escrows()->list(['merchantId' => 'm1']);

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['escrows']);
        self::assertSame('v1/escrows/merchant/m1', $this->requestHistory[0]['request']->getUri()->getPath());
    }

    public function testReleasePostsWithoutBodyWhenNoParamsGiven(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope(['id' => 'esc-1', 'status' => 'released'])],
        ]);

        $result = $client->escrows()->release('esc-1');

        self::assertSame('released', $result['status']);
        $request = $this->requestHistory[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('v1/escrows/esc-1/release', $request->getUri()->getPath());
    }

    public function testConfirmDeliveryWithTrackingReference(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope(['id' => 'esc-1'])],
        ]);

        $client->escrows()->confirmDelivery('esc-1', ['trackingReference' => 'DHL123']);

        $body = json_decode((string) $this->requestHistory[0]['request']->getBody(), true);
        self::assertSame('DHL123', $body['trackingReference']);
    }
}

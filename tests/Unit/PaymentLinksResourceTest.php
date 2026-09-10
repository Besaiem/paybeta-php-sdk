<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use PHPUnit\Framework\TestCase;

class PaymentLinksResourceTest extends TestCase
{
    use MockClientFactory;

    public function testCreateUnwrapsEnvelopeAndPostsToV1Path(): void
    {
        $client = $this->makeClient([
            ['status' => 201, 'body' => $this->envelope([
                'id' => 'link-1',
                'token' => 'tok123',
                'checkoutUrl' => 'https://checkout.paystack.com/abc',
                'status' => 'ACTIVE',
            ])],
        ]);

        $result = $client->paymentLinks()->create([
            'merchantId' => 'm1',
            'amount' => 500000,
            'description' => 'Order #1',
            'sellerEmail' => 'seller@example.com',
            'buyerEmail' => 'buyer@example.com',
            'buyerPhone' => '+2348012345678',
        ]);

        self::assertSame('link-1', $result['id']);
        self::assertSame('https://checkout.paystack.com/abc', $result['checkoutUrl']);

        $request = $this->requestHistory[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('v1/payment-links', $request->getUri()->getPath());
        self::assertSame('pb_test_fake', $request->getHeaderLine('X-API-Key'));

        $body = json_decode((string) $request->getBody(), true);
        self::assertSame('m1', $body['merchantId']);
    }

    public function testRetrieveIsAGetRequest(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope(['id' => 'link-1', 'status' => 'ACTIVE'])],
        ]);

        $result = $client->paymentLinks()->retrieve('tok123');

        self::assertSame('ACTIVE', $result['status']);
        $request = $this->requestHistory[0]['request'];
        self::assertSame('GET', $request->getMethod());
        self::assertSame('v1/payment-links/tok123', $request->getUri()->getPath());
    }

    public function testListForMerchantReturnsBareArray(): void
    {
        $client = $this->makeClient([
            ['status' => 200, 'body' => $this->envelope([
                ['id' => 'link-1'],
                ['id' => 'link-2'],
            ])],
        ]);

        $result = $client->paymentLinks()->listForMerchant('m1');

        self::assertCount(2, $result);
        $request = $this->requestHistory[0]['request'];
        self::assertSame('v1/payment-links/merchant/m1', $request->getUri()->getPath());
    }
}

<?php

declare(strict_types=1);

namespace Paybeta\Tests\Unit;

use Paybeta\Exceptions\PaybetaException;
use Paybeta\Webhooks\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private function sign(string $timestamp, string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);
    }

    public function testValidSignatureParsesPayload(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $timestamp = '1700000000';
        $body = json_encode(['id' => 'evt_1', 'eventType' => 'payment.received', 'timestamp' => $timestamp, 'data' => ['id' => 'p1']]);

        $event = $verifier->constructEvent($body, $this->sign($timestamp, $body), $timestamp);

        self::assertSame('evt_1', $event['id']);
        self::assertSame('payment.received', $event['eventType']);
    }

    public function testAcceptsBareHexSignatureWithoutPrefix(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $timestamp = '1700000000';
        $body = json_encode(['id' => 'evt_1', 'eventType' => 'payment.received', 'timestamp' => $timestamp, 'data' => []]);
        $bareSignature = hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $event = $verifier->constructEvent($body, $bareSignature, $timestamp);

        self::assertSame('evt_1', $event['id']);
    }

    public function testInvalidSignatureThrows(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $timestamp = '1700000000';
        $body = json_encode(['id' => 'evt_1']);

        $this->expectException(PaybetaException::class);
        $this->expectExceptionMessage('Webhook signature verification failed');

        $verifier->constructEvent($body, 'sha256=' . str_repeat('0', 64), $timestamp);
    }

    public function testMissingSecretThrows(): void
    {
        $verifier = new WebhookVerifier('');

        $this->expectException(PaybetaException::class);
        $this->expectExceptionMessage('A webhook secret must be configured');

        $verifier->constructEvent('{}', 'sha256=abc', '1700000000');
    }

    public function testTamperedBodyFailsVerification(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $timestamp = '1700000000';
        $originalBody = json_encode(['id' => 'evt_1', 'amount' => 100]);
        $signature = $this->sign($timestamp, $originalBody);

        $tamperedBody = json_encode(['id' => 'evt_1', 'amount' => 999999]);

        $this->expectException(PaybetaException::class);
        $verifier->constructEvent($tamperedBody, $signature, $timestamp);
    }
}

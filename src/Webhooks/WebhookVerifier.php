<?php

declare(strict_types=1);

namespace Paybeta\Webhooks;

use Paybeta\Exceptions\PaybetaException;

/**
 * Verifies the signature on an incoming webhook and parses the payload.
 *
 * PayBeta signs the concatenation of the delivery timestamp and the raw
 * request body — HMAC-SHA256(secret, "{timestamp}.{rawBody}") — and sends
 * the result hex-encoded, prefixed with "sha256=", in the
 * X-PayBeta-Signature header. The timestamp itself arrives separately in
 * X-PayBeta-Timestamp, so both headers are required, not just the signature.
 */
class WebhookVerifier
{
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * @return array{id: string, eventType: string, timestamp: string, data: array<mixed>}
     */
    public function constructEvent(string $rawBody, string $signature, string $timestamp): array
    {
        if ($this->secret === '') {
            throw new PaybetaException('A webhook secret must be configured to verify webhook signatures');
        }

        // Accept the header verbatim ("sha256=<hex>") or a bare hex digest,
        // so callers who've already stripped the prefix don't get a
        // confusing "invalid signature format" error.
        $hexSignature = str_starts_with($signature, 'sha256=')
            ? substr($signature, strlen('sha256='))
            : $signature;

        $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $this->secret);

        if (!hash_equals($expected, $hexSignature)) {
            throw new PaybetaException('Webhook signature verification failed');
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new PaybetaException('Webhook payload is not valid JSON');
        }

        return $decoded;
    }
}

<?php

declare(strict_types=1);

namespace Paybeta;

use GuzzleHttp\ClientInterface;
use Paybeta\Resources\DisputesResource;
use Paybeta\Resources\EscrowsResource;
use Paybeta\Resources\PaymentLinksResource;
use Paybeta\Resources\PaymentsResource;
use Paybeta\Resources\TransactionsResource;
use Paybeta\Webhooks\WebhookVerifier;

class PaybetaClient
{
    private PaymentLinksResource $paymentLinks;
    private TransactionsResource $transactions;
    private PaymentsResource $payments;
    private EscrowsResource $escrows;
    private DisputesResource $disputes;
    private WebhookVerifier $webhooks;

    /**
     * @param string $apiKey API key from the PayBeta dashboard (pb_live_* or pb_test_*).
     * @param string $baseUrl Override the default API base URL. Defaults to https://api.usepaybeta.com.
     * @param string|null $webhookSecret Webhook signing secret used to verify incoming webhook payloads.
     * @param int $timeout Request timeout in seconds. Defaults to 30.
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.usepaybeta.com',
        ?string $webhookSecret = null,
        int $timeout = 30,
        ?ClientInterface $httpClient = null,
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('PaybetaClient: apiKey is required');
        }

        $http = new HttpClient($apiKey, $baseUrl, $timeout, $httpClient);

        $this->paymentLinks = new PaymentLinksResource($http);
        $this->transactions = new TransactionsResource($http);
        $this->payments = new PaymentsResource($http);
        $this->escrows = new EscrowsResource($http);
        $this->disputes = new DisputesResource($http);
        $this->webhooks = new WebhookVerifier($webhookSecret ?? '');
    }

    // Exposed as methods, not public properties, so the same accessor works
    // both directly ($client->paymentLinks()->create(...)) and through the
    // Laravel facade (Paybeta::paymentLinks()->create(...)) — Facade's
    // __callStatic only forwards method calls to the resolved instance, not
    // property reads.

    public function paymentLinks(): PaymentLinksResource
    {
        return $this->paymentLinks;
    }

    public function transactions(): TransactionsResource
    {
        return $this->transactions;
    }

    public function payments(): PaymentsResource
    {
        return $this->payments;
    }

    public function escrows(): EscrowsResource
    {
        return $this->escrows;
    }

    public function disputes(): DisputesResource
    {
        return $this->disputes;
    }

    public function webhooks(): WebhookVerifier
    {
        return $this->webhooks;
    }
}

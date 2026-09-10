<?php

declare(strict_types=1);

namespace Paybeta\Resources;

class PaymentsResource extends Resource
{
    /**
     * @param array{
     *     merchantId: string,
     *     transactionId: string,
     *     amount: int,
     *     currency: string,
     *     paymentMethod: string,
     *     pspType: string,
     *     customerEmail: string,
     *     customerName?: string,
     *     redirectUrl?: string,
     *     metadata?: array<string, mixed>,
     * } $params Integer amount in the smallest currency unit (kobo for NGN) — not a decimal.
     */
    public function initiate(array $params, ?string $idempotencyKey = null): array
    {
        if ($idempotencyKey !== null) {
            $params['idempotencyKey'] = $idempotencyKey;
        }

        return $this->http->post('/payments', $params);
    }

    /**
     * Lists payments. The bare /payments endpoint is platform-role only —
     * an API-key (merchant) caller gets a 403 there — so passing
     * merchantId routes to /payments/merchant/:merchantId instead.
     *
     * @param array{merchantId?: string, limit?: int, offset?: int} $params
     */
    public function list(array $params = []): array
    {
        $merchantId = $params['merchantId'] ?? null;
        unset($params['merchantId']);
        $path = $merchantId !== null ? "/payments/merchant/{$merchantId}" : '/payments';

        return $this->http->get($path, $params);
    }

    public function retrieve(string $id): array
    {
        return $this->http->get("/payments/{$id}");
    }

    /** Call this when the customer returns from the PSP redirect to confirm the payment status. */
    public function verify(string $id): array
    {
        return $this->http->post("/payments/{$id}/verify");
    }

    public function retry(string $id): array
    {
        return $this->http->post("/payments/{$id}/retry");
    }

    public function listAttempts(string $id): array
    {
        return $this->http->get("/payments/{$id}/attempts");
    }
}

<?php

declare(strict_types=1);

namespace Paybeta\Resources;

class EscrowsResource extends Resource
{
    /**
     * @param array{
     *     transactionId: string,
     *     merchantId: string,
     *     buyerEmail: string,
     *     buyerPhone: string,
     *     sellerEmail: string,
     *     amount: float,
     *     currency: string,
     *     releasePolicy?: array{conditionLogic?: string, conditions?: array<int, array{type: string, config?: array<string, mixed>}>},
     * } $params Decimal amount in the major currency unit (naira) — the API converts to kobo itself.
     */
    public function create(array $params, ?string $idempotencyKey = null): array
    {
        if ($idempotencyKey !== null) {
            $params['idempotencyKey'] = $idempotencyKey;
        }

        return $this->http->post('/escrows', $params);
    }

    /**
     * Lists escrows. The bare /escrows endpoint is platform-role only — an
     * API-key (merchant) caller gets a 403 there — so passing merchantId
     * routes to /escrows/merchant/:merchantId instead. Unlike
     * payments/transactions/disputes, this returns a
     * {escrows, total, limit, offset} envelope rather than a bare array.
     *
     * @param array{merchantId?: string, status?: string, limit?: int, offset?: int} $params
     */
    public function list(array $params = []): array
    {
        $merchantId = $params['merchantId'] ?? null;
        unset($params['merchantId']);
        $path = $merchantId !== null ? "/escrows/merchant/{$merchantId}" : '/escrows';

        return $this->http->get($path, $params);
    }

    public function retrieve(string $id): array
    {
        return $this->http->get("/escrows/{$id}");
    }

    /** All three amounts come back as decimal strings (kobo / 100), not numbers. */
    public function retrieveBalance(string $id): array
    {
        return $this->http->get("/escrows/{$id}/balance");
    }

    public function retrieveConditions(string $id): array
    {
        return $this->http->get("/escrows/{$id}/conditions");
    }

    /**
     * @param array{idempotencyKey?: string} $params actorId/actorType are
     *     never accepted from the client — the API always attributes the
     *     action to the authenticated caller.
     */
    public function release(string $id, array $params = []): array
    {
        return $this->http->post("/escrows/{$id}/release", $params ?: null);
    }

    public function refund(string $id): array
    {
        return $this->http->post("/escrows/{$id}/refund");
    }

    public function dispute(string $id): array
    {
        return $this->http->post("/escrows/{$id}/dispute");
    }

    public function cancel(string $id): array
    {
        return $this->http->post("/escrows/{$id}/cancel");
    }

    /** @param array{trackingReference?: string, idempotencyKey?: string} $params */
    public function confirmDelivery(string $id, array $params = []): array
    {
        return $this->http->post("/escrows/{$id}/confirm-delivery", $params ?: null);
    }

    /** @param array{idempotencyKey?: string} $params */
    public function confirmBuyer(string $id, array $params = []): array
    {
        return $this->http->post("/escrows/{$id}/confirm-buyer", $params ?: null);
    }
}

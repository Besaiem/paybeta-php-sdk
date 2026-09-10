<?php

declare(strict_types=1);

namespace Paybeta\Resources;

class TransactionsResource extends Resource
{
    /**
     * @param array{
     *     merchantId: string,
     *     buyerEmail: string,
     *     buyerPhone: string,
     *     sellerEmail: string,
     *     amount: float,
     *     currency: string,
     *     metadata?: array<string, mixed>,
     * } $params Decimal amount in the major currency unit (naira), unlike Payment amounts which are minor-unit (kobo).
     */
    public function create(array $params, ?string $idempotencyKey = null): array
    {
        if ($idempotencyKey !== null) {
            $params['idempotencyKey'] = $idempotencyKey;
        }

        return $this->http->post('/transactions', $params);
    }

    /**
     * Lists transactions. The bare /transactions endpoint is platform-role
     * only — an API-key (merchant) caller gets a 403 there — so passing
     * merchantId routes to /transactions/merchant/:merchantId instead.
     *
     * @param array{merchantId?: string, buyerEmail?: string, status?: string, limit?: int, offset?: int} $params
     */
    public function list(array $params = []): array
    {
        $merchantId = $params['merchantId'] ?? null;
        unset($params['merchantId']);
        $path = $merchantId !== null ? "/transactions/merchant/{$merchantId}" : '/transactions';

        return $this->http->get($path, $params);
    }

    public function retrieve(string $id): array
    {
        return $this->http->get("/transactions/{$id}");
    }

    public function listHistory(string $id): array
    {
        return $this->http->get("/transactions/{$id}/history");
    }

    /**
     * Updates a transaction's status (escrow, release, dispute, refund).
     * The gateway allows this via X-API-Key the same as any other
     * transactions:read-permissioned call.
     */
    public function updateStatus(string $id, string $status): array
    {
        return $this->http->patch("/transactions/{$id}/status", ['status' => $status]);
    }
}

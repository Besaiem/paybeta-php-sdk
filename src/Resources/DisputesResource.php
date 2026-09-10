<?php

declare(strict_types=1);

namespace Paybeta\Resources;

class DisputesResource extends Resource
{
    /**
     * All fields below are required by the API — there is no partial /
     * inferred version of opening a dispute.
     *
     * @param array{
     *     transactionId: string,
     *     escrowId: string,
     *     merchantId: string,
     *     buyerEmail: string,
     *     sellerEmail: string,
     *     disputeType: string,
     *     priority: string,
     *     description: string,
     *     amount: int,
     *     currency: string,
     *     openedBy: string,
     *     workflowId?: string,
     * } $params
     */
    public function open(array $params): array
    {
        return $this->http->post('/disputes', $params);
    }

    /**
     * Lists disputes. The bare /disputes endpoint is platform-role only —
     * an API-key (merchant) caller gets a 403 there — so passing
     * merchantId routes to /disputes/merchant/:merchantId instead.
     *
     * @param array{merchantId?: string, status?: string, stage?: string, limit?: int, offset?: int} $params
     */
    public function list(array $params = []): array
    {
        $merchantId = $params['merchantId'] ?? null;
        unset($params['merchantId']);
        $path = $merchantId !== null ? "/disputes/merchant/{$merchantId}" : '/disputes';

        return $this->http->get($path, $params);
    }

    public function retrieve(string $id): array
    {
        return $this->http->get("/disputes/{$id}");
    }

    public function listEvidence(string $id): array
    {
        return $this->http->get("/disputes/{$id}/evidence");
    }

    /**
     * Field names match the API's raw JSON body exactly.
     *
     * @param array{evidenceType: string, uploadedBy: string, fileName: string, fileData: string, mimeType: string, description?: string} $params
     *     fileData is base64-encoded file content.
     */
    public function uploadEvidence(string $id, array $params): array
    {
        return $this->http->post("/disputes/{$id}/evidence", $params);
    }

    /** @param array{outcome: string, notes: string} $params */
    public function resolve(string $id, array $params): array
    {
        return $this->http->post("/disputes/{$id}/resolve", $params);
    }

    /** @param array{reason: string} $params */
    public function cancel(string $id, array $params): array
    {
        return $this->http->post("/disputes/{$id}/cancel", $params);
    }
}

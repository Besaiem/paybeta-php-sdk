<?php

declare(strict_types=1);

namespace Paybeta\Resources;

/**
 * Payment links — the recommended integration path. One call creates the
 * link and opens a checkout session with the PSP in the same request; the
 * response already includes a ready-to-use `checkoutUrl`.
 */
class PaymentLinksResource extends Resource
{
    /**
     * @param array{
     *     merchantId: string,
     *     amount: int,
     *     description: string,
     *     sellerEmail: string,
     *     buyerEmail: string,
     *     buyerPhone: string,
     *     currency?: string,
     *     reference?: string,
     *     buyerName?: string,
     *     pspType?: string,
     *     paymentMethod?: string,
     *     redirectUrl?: string,
     *     expiresInHours?: int,
     *     saveAsCustomer?: bool,
     * } $params
     */
    public function create(array $params): array
    {
        return $this->http->post('/payment-links', $params);
    }

    /** Returns a bare array — there is no pagination envelope on this endpoint. */
    public function listForMerchant(string $merchantId): array
    {
        return $this->http->get("/payment-links/merchant/{$merchantId}");
    }

    /** Public endpoint, no API key needed. Returns 410 once the link has expired or been used. */
    public function retrieve(string $token): array
    {
        return $this->http->get("/payment-links/{$token}");
    }

    /**
     * Public endpoint. Verifies the payment's live status with the PSP —
     * this is what a buyer's browser calls after being redirected back
     * from checkout, and also creates the underlying escrow transaction
     * the first time a payment is confirmed.
     */
    public function checkCompletion(string $token): array
    {
        return $this->http->get("/payment-links/complete/{$token}");
    }

    /**
     * Advanced/public: (re)creates a checkout session for a link when
     * you're building a custom checkout page instead of sending the buyer
     * straight to `checkoutUrl`. Most integrations don't need this.
     *
     * @param array{
     *     buyerEmail: string,
     *     buyerPhone: string,
     *     paymentMethod: string,
     *     pspType: string,
     *     buyerName?: string,
     * } $params
     */
    public function initiate(string $token, array $params): array
    {
        return $this->http->post("/payment-links/{$token}/initiate", $params);
    }
}

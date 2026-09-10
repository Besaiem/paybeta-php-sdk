<?php

declare(strict_types=1);

namespace Paybeta\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Paybeta\PaybetaClient;

/**
 * @method static \Paybeta\Resources\PaymentLinksResource paymentLinks()
 * @method static \Paybeta\Resources\TransactionsResource transactions()
 * @method static \Paybeta\Resources\PaymentsResource payments()
 * @method static \Paybeta\Resources\EscrowsResource escrows()
 * @method static \Paybeta\Resources\DisputesResource disputes()
 * @method static \Paybeta\Webhooks\WebhookVerifier webhooks()
 *
 * @see PaybetaClient
 */
class Paybeta extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaybetaClient::class;
    }
}

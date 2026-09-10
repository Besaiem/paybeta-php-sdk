# paybetaby/php-sdk

Official PHP SDK for the [PayBeta](https://usepaybeta.com) payments API, with first-class Laravel support (auto-discovered service provider, `Paybeta` facade, publishable config). Framework-agnostic at its core — works in any PHP 8.1+ project.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Laravel Quick Start](#laravel-quick-start)
- [Plain PHP Quick Start](#plain-php-quick-start)
- [Authentication](#authentication)
- [Configuration](#configuration)
- [Resources](#resources)
  - [Payment Links](#payment-links)
  - [Transactions](#transactions)
  - [Payments](#payments)
  - [Escrows](#escrows)
  - [Disputes](#disputes)
  - [Webhooks](#webhooks)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [License](#license)

---

## Requirements

- PHP **8.1** or later
- [Guzzle](https://docs.guzzlephp.org/) 7.8+ (installed automatically as a dependency)
- Laravel 10, 11, or 12 — only if you want the service provider / facade (the core client works without Laravel)
- A PayBeta merchant account — [sign up at usepaybeta.com](https://usepaybeta.com)
- An API key from your PayBeta dashboard (`pb_live_…` for production, `pb_test_…` for sandbox)

---

## Installation

```bash
composer require paybetaby/php-sdk
```

---

## Laravel Quick Start

The service provider and `Paybeta` facade are auto-discovered — no manual registration needed.

**1. Add your credentials to `.env`:**

```env
PAYBETA_API_KEY=pb_test_your-key-here
PAYBETA_MERCHANT_ID=your-merchant-id
PAYBETA_WEBHOOK_SECRET=your-webhook-signing-secret
```

**2. (Optional) Publish the config file** if you want to change defaults (base URL, timeout):

```bash
php artisan vendor:publish --tag=paybeta-config
```

This publishes `config/paybeta.php`.

**3. Use the facade, or inject the client:**

```php
use Paybeta\Laravel\Facades\Paybeta;

$link = Paybeta::paymentLinks()->create([
    'merchantId'  => config('paybeta.merchant_id'),
    'amount'      => 500000, // kobo — ₦5,000.00
    'description' => 'Order #12345',
    'sellerEmail' => 'seller@example.com',
    'buyerEmail'  => 'buyer@example.com',
    'buyerPhone'  => '+2348012345678',
]);

return redirect($link['checkoutUrl']);
```

Or via constructor/method injection, using the `Paybeta\PaybetaClient` class directly:

```php
use Paybeta\PaybetaClient;

class CheckoutController extends Controller
{
    public function store(Request $request, PaybetaClient $paybeta)
    {
        $link = $paybeta->paymentLinks()->create([...]);

        return redirect($link['checkoutUrl']);
    }
}
```

---

## Plain PHP Quick Start

No framework required — the client works standalone.

```php
require 'vendor/autoload.php';

use Paybeta\PaybetaClient;

$paybeta = new PaybetaClient(
    apiKey: $_ENV['PAYBETA_API_KEY'],
    webhookSecret: $_ENV['PAYBETA_WEBHOOK_SECRET'] ?? null,
);

// One call creates the link AND opens a checkout session — the response
// already has a ready-to-use checkoutUrl. This is the recommended way to
// accept a payment; no need to create a transaction yourself first.
$link = $paybeta->paymentLinks()->create([
    'merchantId'  => 'your-merchant-id',
    'amount'      => 500000, // kobo
    'description' => 'Order #12345',
    'sellerEmail' => 'seller@example.com',
    'buyerEmail'  => 'buyer@example.com',
    'buyerPhone'  => '+2348012345678',
]);

header("Location: {$link['checkoutUrl']}");
```

---

## Authentication

PayBeta uses **API key authentication**. Pass your key once when constructing the client (or via `config/paybeta.php` in Laravel) — every request carries it automatically via the `X-API-Key` header.

| Key prefix  | Environment          |
|-------------|-----------------------|
| `pb_live_…` | Production (live)    |
| `pb_test_…` | Sandbox (test mode)  |

> **Keep your API key secret.** Never embed it in client-side code or commit it to version control. Use environment variables (`.env`, never committed).

---

## Configuration

### Plain PHP — `PaybetaClient` constructor

```php
$paybeta = new PaybetaClient(
    apiKey: 'pb_live_...',                       // required
    baseUrl: 'https://api.usepaybeta.com',       // optional — override for staging/self-hosted
    webhookSecret: 'your-webhook-secret',        // optional — required only for webhooks()->constructEvent()
    timeout: 30,                                  // optional — request timeout in seconds
);
```

### Laravel — `config/paybeta.php`

| Key               | Env var                   | Default                       |
|-------------------|----------------------------|--------------------------------|
| `api_key`         | `PAYBETA_API_KEY`          | `''`                            |
| `merchant_id`     | `PAYBETA_MERCHANT_ID`      | `''`                            |
| `base_url`        | `PAYBETA_BASE_URL`         | `https://api.usepaybeta.com`  |
| `webhook_secret`  | `PAYBETA_WEBHOOK_SECRET`   | `''`                            |
| `timeout`         | `PAYBETA_TIMEOUT`          | `30`                            |

`merchant_id` isn't read by the SDK itself — it's kept in config as a single place for the rest of your app to pull it from, since every payment-link/transaction/escrow call needs it.

---

## Resources

Every resource method returns a plain PHP array (the API's JSON response, decoded with `json_decode($json, true)` and unwrapped from the `{status, data, timestamp}` envelope every endpoint sends).

### Payment Links

The **recommended integration path**. One call creates the link and opens a checkout session with the PSP — the response includes a ready-to-use `checkoutUrl`.

```php
$link = $paybeta->paymentLinks()->create([
    'merchantId'  => 'your-merchant-id',
    'amount'      => 500000,               // kobo — smallest unit
    'description' => 'Order #12345',
    'sellerEmail' => 'seller@example.com',
    'buyerEmail'  => 'buyer@example.com',
    'buyerPhone'  => '+2348012345678',     // E.164 — SMS/WhatsApp delivery fallback
    // Optional:
    'currency'       => 'NGN',              // defaults to NGN
    'reference'      => 'ORD-2026-001',
    'buyerName'      => 'Ada Okafor',
    'pspType'        => 'PAYSTACK',         // defaults to PAYSTACK
    'paymentMethod'  => 'CARD',             // defaults to CARD
    'redirectUrl'    => 'https://yoursite.com/order/confirmed',
    'expiresInHours' => 72,                 // defaults to 72
]);

// $link['checkoutUrl'] — send the buyer here
// $link['token']       — the link's identifier, for retrieve()/checkCompletion()
```

```php
// List a merchant's payment links (bare array, no pagination envelope)
$links = $paybeta->paymentLinks()->listForMerchant('your-merchant-id');

// Public — no API key needed. Retrieve link details by token.
$link = $paybeta->paymentLinks()->retrieve($token);

// Public — what a buyer's browser calls after the PSP redirect. Verifies
// the payment's live status and creates the underlying escrow transaction
// the first time a payment is confirmed.
$status = $paybeta->paymentLinks()->checkCompletion($token);
if ($status['status'] === 'FUNDED') {
    // fulfil the order
}

// Advanced/public — building a custom checkout page instead of using
// checkoutUrl directly. Most integrations don't need this.
$session = $paybeta->paymentLinks()->initiate($token, [
    'buyerEmail'    => 'buyer@example.com',
    'buyerPhone'    => '+2348012345678',
    'paymentMethod' => 'CARD',
    'pspType'       => 'PAYSTACK',
]);
```

---

### Transactions

A **transaction** represents the commercial relationship between a buyer and seller. Payment links create one for you automatically — create your own directly only if you're building a lower-level, custom flow.

```php
$transaction = $paybeta->transactions()->create([
    'merchantId'  => 'your-merchant-id',
    'buyerEmail'  => 'buyer@example.com',
    'buyerPhone'  => '+2348012345678',
    'sellerEmail' => 'seller@example.com',
    'amount'      => 1500,   // decimal naira (₦1,500.00) — NOT kobo, unlike Payment amounts
    'currency'    => 'NGN',
], idempotencyKey: 'unique-key-per-attempt');

echo $transaction['id'];     // UUID
echo $transaction['status']; // 'INITIATED'
```

```php
// List — merchantId is required for an API-key caller: the bare
// /transactions endpoint is platform-role only and 403s a merchant key.
$transactions = $paybeta->transactions()->list([
    'merchantId' => 'your-merchant-id',
    'status'     => 'FUNDED',
    'limit'      => 20,
]);

$transaction = $paybeta->transactions()->retrieve('txn-uuid');
$events = $paybeta->transactions()->listHistory('txn-uuid');

// Advance the transaction's lifecycle directly (escrow/release/dispute/refund)
$paybeta->transactions()->updateStatus('txn-uuid', 'RELEASED');
```

**Transaction statuses:** `INITIATED` → `FUNDED` → `IN_ESCROW` → `RELEASED` / `DISPUTED` / `REFUNDED`

---

### Payments

A **payment** records a customer's attempt to fund a transaction via a PSP (Paystack, Flutterwave). Payment links handle this for you — use these methods directly only for a custom, lower-level flow.

```php
$payment = $paybeta->payments()->initiate([
    'merchantId'    => 'your-merchant-id',
    'transactionId' => $transaction['id'],
    'amount'        => 150000,     // kobo — integer minor-unit, unlike Transaction amounts
    'currency'      => 'NGN',
    'paymentMethod' => 'CARD',     // CARD | BANK_TRANSFER | USSD | MOBILE_MONEY | BANK_ACCOUNT
    'pspType'       => 'PAYSTACK', // PAYSTACK | FLUTTERWAVE | BANK_DIRECT
    'customerEmail' => 'buyer@example.com',
]);

// Redirect your customer to complete payment
header("Location: {$payment['authorizationUrl']}");
```

```php
// Call when the customer returns from the PSP redirect — never trust the
// query string alone, always re-verify.
$payment = $paybeta->payments()->verify($paymentId);
if ($payment['status'] === 'COMPLETED') {
    // fulfil the order
}

$payment = $paybeta->payments()->retrieve($paymentId);
$payments = $paybeta->payments()->list(['merchantId' => 'your-merchant-id']);
$paybeta->payments()->retry($paymentId);
$attempts = $paybeta->payments()->listAttempts($paymentId);
```

**Payment statuses:** `PENDING` → `PROCESSING` → `COMPLETED` / `FAILED` / `CANCELLED`

---

### Escrows

**Escrows** hold funds securely between buyer and seller until configurable release conditions are met. Available on Growth and Enterprise plans.

```php
$escrow = $paybeta->escrows()->create([
    'transactionId' => $transaction['id'],
    'merchantId'    => 'your-merchant-id',
    'buyerEmail'    => 'buyer@example.com',
    'buyerPhone'    => '+2348012345678',
    'sellerEmail'   => 'seller@example.com',
    'amount'        => 1500,   // decimal naira — the API converts to kobo itself
    'currency'      => 'NGN',
    'releasePolicy' => [       // optional
        'conditionLogic' => 'AND',  // release only when ALL conditions are met
        'conditions' => [
            ['type' => 'DELIVERY_CONFIRMATION'],
            ['type' => 'BUYER_CONFIRMATION'],
        ],
    ],
]);
```

**Condition types:** `DELIVERY_CONFIRMATION`, `BUYER_CONFIRMATION`, `TIME_BASED`, `MANUAL_APPROVAL`
**Condition logic:** `AND` (all must be met) / `OR` (any one triggers release)

```php
$paybeta->escrows()->release($escrowId, ['idempotencyKey' => 'release-once']);
$paybeta->escrows()->confirmDelivery($escrowId, ['trackingReference' => 'DHL123456']);
$paybeta->escrows()->confirmBuyer($escrowId);
$paybeta->escrows()->dispute($escrowId);
$paybeta->escrows()->refund($escrowId);
$paybeta->escrows()->cancel($escrowId);

$escrow = $paybeta->escrows()->retrieve($escrowId);
// $escrow['status'] is lowercase: 'funded' | 'pending_release' | 'released' | ...
// $escrow['amount'] is kobo (integer) — NOT divided down on the way out, unlike on create

// All three amounts are decimal strings (already divided from kobo)
$balance = $paybeta->escrows()->retrieveBalance($escrowId);

// Returns an envelope, not a bare array
$conditions = $paybeta->escrows()->retrieveConditions($escrowId);

// Unlike payments/transactions/disputes, list() returns a
// {escrows, total, limit, offset} envelope, not a bare array
$result = $paybeta->escrows()->list(['merchantId' => 'your-merchant-id', 'status' => 'funded']);
```

**Escrow statuses (lowercase):** `created` → `funded` → `pending_release` → `released` / `disputed` / `refunded` / `cancelled`

---

### Disputes

A **dispute** is opened when buyer and seller cannot agree. PayBeta provides a structured arbitration workflow.

```php
// All fields below are required by the API — no partial/inferred version.
$dispute = $paybeta->disputes()->open([
    'transactionId' => $transaction['id'],
    'escrowId'      => $escrow['id'],
    'merchantId'    => 'your-merchant-id',
    'buyerEmail'    => 'buyer@example.com',
    'sellerEmail'   => 'seller@example.com',
    'disputeType'   => 'NON_DELIVERY',
    'priority'      => 'HIGH',
    'description'   => 'Item not as described.',
    'amount'        => 150000, // kobo
    'currency'      => 'NGN',
    'openedBy'      => 'BUYER', // BUYER | SELLER
]);
```

```php
// Field names match the API's JSON body exactly
$paybeta->disputes()->uploadEvidence($disputeId, [
    'evidenceType' => 'IMAGE',  // IMAGE | DOCUMENT | VIDEO | OTHER
    'uploadedBy'   => 'BUYER',  // BUYER | SELLER | ARBITRATOR
    'fileName'     => 'packaging.jpg',
    'fileData'     => base64_encode(file_get_contents('packaging.jpg')),
    'mimeType'     => 'image/jpeg',
    'description'  => 'Photo of damaged packaging',
]);

$paybeta->disputes()->resolve($disputeId, [
    'outcome' => 'BUYER_WINS', // BUYER_WINS | SELLER_WINS | PARTIAL_REFUND | PARTIAL_RELEASE | SPLIT | CANCELLED
    'notes'   => 'Evidence confirmed item was not delivered.',
]);

$paybeta->disputes()->cancel($disputeId, ['reason' => 'Parties reached mutual agreement.']);

$dispute = $paybeta->disputes()->retrieve($disputeId);
$disputes = $paybeta->disputes()->list(['merchantId' => 'your-merchant-id', 'status' => 'OPENED']);
$evidence = $paybeta->disputes()->listEvidence($disputeId);
```

---

### Webhooks

PayBeta sends signed webhook events to your server when key state changes occur (payment completed, escrow released, dispute opened, etc.).

`constructEvent()` verifies `HMAC-SHA256(webhookSecret, "{timestamp}.{rawBody}")` against the `X-PayBeta-Signature` header (sent as `sha256=<hex>`) — both the signature *and* `X-PayBeta-Timestamp` headers are required, since the timestamp is part of what's actually signed, not just metadata.

#### Laravel example

```php
use Illuminate\Http\Request;
use Paybeta\Exceptions\PaybetaException;
use Paybeta\Laravel\Facades\Paybeta;

class PaybetaWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $event = Paybeta::webhooks()->constructEvent(
                $request->getContent(),
                $request->header('X-PayBeta-Signature', ''),
                $request->header('X-PayBeta-Timestamp', ''),
            );
        } catch (PaybetaException $e) {
            report($e);
            return response('Webhook signature verification failed', 400);
        }

        match ($event['eventType']) {
            'payment.received'  => $this->handlePaymentReceived($event['data']),
            'transaction.funded' => $this->handleTransactionFunded($event['data']),
            'escrow.released'   => $this->handleEscrowReleased($event['data']),
            'dispute.opened'    => $this->handleDisputeOpened($event['data']),
            default             => null,
        };

        return response()->json(['received' => true]);
    }
}
```

Register the route **outside** any middleware that reads/mutates the raw body before you do (e.g. Laravel's CSRF middleware doesn't apply to API routes by default, but double-check any custom body-parsing middleware) — `constructEvent()` needs the exact raw bytes PayBeta signed.

```php
// routes/api.php
Route::post('/webhooks/paybeta', PaybetaWebhookController::class);
```

#### Plain PHP example

```php
$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYBETA_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_PAYBETA_TIMESTAMP'] ?? '';

try {
    $event = $paybeta->webhooks()->constructEvent($rawBody, $signature, $timestamp);
} catch (\Paybeta\Exceptions\PaybetaException $e) {
    http_response_code(400);
    exit('Webhook signature verification failed');
}

// ... handle $event['eventType'] / $event['data']
```

#### Event types

Exactly the events PayBeta can emit — there is no `dispute.cancelled` or any `escrow.*` event besides `escrow.released`.

| Event type              | Description                                 |
|--------------------------|----------------------------------------------|
| `transaction.created`   | New transaction created                     |
| `transaction.funded`    | Customer's payment cleared; funds received  |
| `transaction.escrowed`  | Funds moved into escrow hold                |
| `transaction.released`  | Funds released to seller                    |
| `transaction.disputed`  | Dispute opened on transaction               |
| `transaction.refunded`  | Transaction refunded to buyer               |
| `payment.received`      | Payment confirmed as successful             |
| `payment.failed`        | Payment failed or declined                  |
| `dispute.opened`        | Dispute opened                              |
| `dispute.resolved`      | Dispute resolved with outcome               |
| `escrow.released`       | Escrow funds disbursed to seller            |

---

## Error Handling

### `PaybetaApiException`

Thrown when the API returns a non-2xx response.

```php
use Paybeta\Exceptions\PaybetaApiException;

try {
    $escrow = $paybeta->escrows()->create([...]);
} catch (PaybetaApiException $e) {
    echo $e->getMessage(); // Human-readable error message
    echo $e->status;       // HTTP status code (e.g. 402, 403, 404)
    echo $e->errorCode;    // Machine-readable code (e.g. 'FEATURE_NOT_AVAILABLE')
    echo $e->traceId;      // PayBeta trace ID for support
}
```

**Common error codes:**

| Code                     | Status | Meaning                                                     |
|---------------------------|--------|---------------------------------------------------------------|
| `FEATURE_NOT_AVAILABLE`  | 403    | Feature not enabled on your plan (e.g. escrow on Starter)    |
| `VOLUME_LIMIT_EXCEEDED`  | 402    | Monthly volume limit reached — upgrade your plan             |
| `API_KEY_LIMIT_EXCEEDED` | 402    | API key count limit reached for your plan                    |
| `NOT_FOUND`              | 404    | Resource not found                                            |
| `UNAUTHORIZED`           | 401    | Invalid or missing API key                                    |
| `TOO_MANY_REQUESTS`      | 429    | Rate limit exceeded                                            |
| `BAD_REQUEST`            | 400    | Validation error — check the error message for field details |

### `PaybetaException`

The base exception — thrown for anything that isn't an API response: request timeout, DNS/connection failures, webhook signature failure, missing configuration. `PaybetaApiException` extends this, so catching `PaybetaException` catches both.

```php
use Paybeta\Exceptions\PaybetaException;

try {
    $event = $paybeta->webhooks()->constructEvent($rawBody, $signature, $timestamp);
} catch (PaybetaException $e) {
    // signature mismatch, missing webhookSecret, network failure, etc.
    echo $e->getMessage();
}
```

---

## Testing

```bash
git clone https://github.com/Besaiem/paybeta-php-sdk
cd paybeta-php-sdk

composer install
composer test       # PHPUnit, mocked HTTP — no real API calls or credentials needed
composer cs-check    # code style check
composer cs-fix      # auto-fix code style
```

---

## License

MIT © PayBeta

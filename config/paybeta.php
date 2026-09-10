<?php

// Publish with: php artisan vendor:publish --tag=paybeta-config

return [
    /*
     * API key from Settings -> API Keys in the PayBeta dashboard.
     * pb_live_... for production, pb_test_... for sandbox.
     */
    'api_key' => env('PAYBETA_API_KEY', ''),

    /*
     * Your merchant ID, from Settings -> Business in the PayBeta dashboard.
     * Not read by the SDK itself — kept here as a single place for the rest
     * of your app to pull it from (every payments/transactions/escrows
     * call needs it), mirroring the Next.js example app's env convention.
     */
    'merchant_id' => env('PAYBETA_MERCHANT_ID', ''),

    /*
     * Override the API base URL — useful for staging or a self-hosted
     * instance. Defaults to the public production gateway.
     */
    'base_url' => env('PAYBETA_BASE_URL', 'https://api.usepaybeta.com'),

    /*
     * HMAC secret used to verify incoming webhook signatures, from
     * Settings -> Webhooks in the PayBeta dashboard.
     */
    'webhook_secret' => env('PAYBETA_WEBHOOK_SECRET', ''),

    /*
     * Request timeout in seconds.
     */
    'timeout' => (int) env('PAYBETA_TIMEOUT', 30),
];

<?php

declare(strict_types=1);

namespace Paybeta\Laravel;

use Illuminate\Support\ServiceProvider;
use Paybeta\PaybetaClient;

class PaybetaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/paybeta.php', 'paybeta');

        $this->app->singleton(PaybetaClient::class, static function ($app) {
            $config = $app['config']->get('paybeta');

            return new PaybetaClient(
                apiKey: (string) ($config['api_key'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://api.usepaybeta.com'),
                webhookSecret: $config['webhook_secret'] ?? null,
                timeout: (int) ($config['timeout'] ?? 30),
            );
        });

        $this->app->alias(PaybetaClient::class, 'paybeta');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/paybeta.php' => $this->app->configPath('paybeta.php'),
            ], 'paybeta-config');
        }
    }

    /** @return array<int, string> */
    public function provides(): array
    {
        return [PaybetaClient::class, 'paybeta'];
    }
}

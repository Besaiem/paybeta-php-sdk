<?php

declare(strict_types=1);

namespace Paybeta;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Paybeta\Exceptions\PaybetaApiException;
use Paybeta\Exceptions\PaybetaException;

/**
 * Thin wrapper around Guzzle that knows PayBeta's own conventions: the
 * gateway's /v1 route table (see below), X-API-Key auth, and the
 * {"status","data","timestamp"} response envelope every endpoint wraps its
 * real payload in.
 */
class HttpClient
{
    private ClientInterface $http;

    public function __construct(
        private readonly string $apiKey,
        string $baseUrl = 'https://api.usepaybeta.com',
        int $timeoutSeconds = 30,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => $timeoutSeconds,
            'http_errors' => false,
        ]);
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     * @return array<mixed>
     */
    public function request(string $method, string $path, ?array $query = null, ?array $body = null): array
    {
        // The public API is served behind api-gateway under a /v1 prefix —
        // the gateway's route table only matches /v1/... and 404s anything
        // else, so every request must go there regardless of how $path is
        // written by the resource classes below.
        $url = '/v1' . $path;

        $options = [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ],
        ];

        if ($query !== null) {
            $options['query'] = array_filter($query, static fn ($v) => $v !== null);
        }

        if ($body !== null) {
            $options['json'] = $body;
        }

        try {
            $response = $this->http->request($method, ltrim($url, '/'), $options);
        } catch (ConnectException $e) {
            throw new PaybetaException("Network request to PayBeta failed: {$e->getMessage()}", $e);
        } catch (RequestException $e) {
            throw new PaybetaException("Request to PayBeta failed: {$e->getMessage()}", $e);
        } catch (GuzzleException $e) {
            throw new PaybetaException("Request to PayBeta failed: {$e->getMessage()}", $e);
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            $payload = $raw !== '' ? ['message' => $raw] : [];
        }

        if ($status < 200 || $status >= 300) {
            $error = $payload['error'] ?? [];
            throw new PaybetaApiException(
                $status,
                $error['code'] ?? (string) $status,
                $error['message'] ?? ($payload['message'] ?? 'Request failed'),
                $error['traceId'] ?? '',
                $error['timestamp'] ?? gmdate('Y-m-d\TH:i:s.v\Z'),
            );
        }

        // Backend wraps every response as { status, data, timestamp } —
        // unwrap transparently here so every resource method keeps working
        // against the raw resource shape.
        return array_key_exists('data', $payload) ? $payload['data'] : $payload;
    }

    /** @param array<string, mixed>|null $query */
    public function get(string $path, ?array $query = null): array
    {
        return $this->request('GET', $path, $query);
    }

    /** @param array<string, mixed>|null $body */
    public function post(string $path, ?array $body = null): array
    {
        return $this->request('POST', $path, null, $body);
    }

    /** @param array<string, mixed>|null $body */
    public function patch(string $path, ?array $body = null): array
    {
        return $this->request('PATCH', $path, null, $body);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }
}

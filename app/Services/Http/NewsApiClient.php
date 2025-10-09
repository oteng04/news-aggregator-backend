<?php

namespace App\Services\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Exceptions\NewsApiException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

class NewsApiClient
{
    private array $config;
    private int $timeout = 30;
    private int $retryAttempts = 3;
    private int $retryDelay = 100; // milliseconds

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        if (empty($this->config['api_key'])) {
            throw new NewsApiException('API key is required');
        }

        if (empty($this->config['base_url'])) {
            throw new NewsApiException('Base URL is required');
        }
    }

    public function get(string $endpoint, array $params = []): Collection
    {
        $url = $this->buildUrl($endpoint);
        $params = array_merge($params, ['api-key' => $this->config['api_key']]);

        return $this->makeRequest('GET', $url, $params);
    }

    public function post(string $endpoint, array $params = [], array $data = []): Collection
    {
        $url = $this->buildUrl($endpoint);
        $params = array_merge($params, ['api-key' => $this->config['api_key']]);

        return $this->makeRequest('POST', $url, $params, $data);
    }

    private function buildUrl(string $endpoint): string
    {
        return rtrim($this->config['base_url'], '/') . '/' . ltrim($endpoint, '/');
    }

    private function makeRequest(string $method, string $url, array $params = [], array $data = []): Collection
    {
        $attempts = 0;

        while ($attempts < $this->retryAttempts) {
            try {
                $startTime = microtime(true);

                $httpClient = Http::timeout($this->timeout);

                if ($method === 'GET') {
                    $response = $httpClient->get($url, $params);
                } else {
                    $response = $httpClient->post($url, array_merge($params, $data));
                }

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    Log::info("API request successful", [
                        'url' => $url,
                        'method' => $method,
                        'duration_ms' => $duration,
                        'status' => $response->status()
                    ]);

                    return collect($response->json());
                }

                // Handle rate limiting
                if ($response->status() === 429) {
                    $retryAfter = $response->header('Retry-After') ?? ($this->retryDelay / 1000);
                    sleep((int) $retryAfter);
                    $attempts++;
                    continue;
                }

                // Handle other errors
                $this->handleErrorResponse($response, $url, $method, $duration);
                break;

            } catch (ConnectionException $e) {
                $attempts++;
                $service = $this->config['name'] ?? 'Unknown Service';

                Log::warning("Network connection failed for {$service}", [
                    'url' => $url,
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts
                ]);

                if ($attempts >= $this->retryAttempts) {
                    throw NewsApiException::networkError($service, $e->getMessage());
                }

                // Exponential backoff
                usleep($this->retryDelay * 1000 * $attempts);

            } catch (RequestException $e) {
                $attempts++;
                $service = $this->config['name'] ?? 'Unknown Service';

                Log::warning("HTTP request failed for {$service}", [
                    'url' => $url,
                    'method' => $method,
                    'status' => $e->response?->status(),
                    'error' => $e->getMessage(),
                    'attempt' => $attempts
                ]);

                if ($attempts >= $this->retryAttempts) {
                    if ($e->response) {
                        $this->handleErrorResponse($e->response, $url, $method, 0);
                    } else {
                        throw NewsApiException::networkError($service, $e->getMessage());
                    }
                }

                // Exponential backoff
                usleep($this->retryDelay * 1000 * $attempts);

            } catch (\Exception $e) {
                $attempts++;
                $service = $this->config['name'] ?? 'Unknown Service';

                Log::warning("Unexpected error for {$service}", [
                    'url' => $url,
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts
                ]);

                if ($attempts >= $this->retryAttempts) {
                    throw new NewsApiException(
                        "Unexpected error after {$this->retryAttempts} attempts: " . $e->getMessage(),
                        500,
                        'API_ERROR',
                        ['service' => $service, 'original_error' => $e->getMessage()],
                        $e
                    );
                }

                // Exponential backoff
                usleep($this->retryDelay * 1000 * $attempts);
            }
        }

        return collect();
    }

    private function handleErrorResponse($response, string $url, string $method, float $duration): void
    {
        $status = $response->status();
        $body = $response->body();

        Log::error("API request failed", [
            'url' => $url,
            'method' => $method,
            'status' => $status,
            'duration_ms' => $duration,
            'response_body' => $body
        ]);

        $service = $this->config['name'] ?? 'Unknown Service';

        // Handle specific HTTP status codes
        switch ($status) {
            case 401:
                throw NewsApiException::invalidApiKey($service);
            case 403:
                throw NewsApiException::invalidApiKey($service);
            case 429:
                $retryAfter = $response->header('Retry-After') ?? 60;
                throw NewsApiException::rateLimitExceeded($service, (int) $retryAfter);
            case 500:
            case 502:
            case 503:
            case 504:
                throw NewsApiException::apiUnavailable($service, ['status_code' => $status]);
            default:
                throw new NewsApiException("API request failed with status {$status}", $status, 'API_ERROR', [
                    'service' => $service,
                    'url' => $url,
                    'method' => $method
                ]);
        }
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setRetryAttempts(int $attempts): self
    {
        $this->retryAttempts = $attempts;
        return $this;
    }
}

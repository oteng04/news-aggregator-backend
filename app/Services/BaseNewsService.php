<?php

namespace App\Services;

use App\Services\Contracts\NewsSourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseNewsService implements NewsSourceInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $providerKey;

    public function __construct()
    {
        $config = config("news.providers.{$this->providerKey}");
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'];
    }

    protected function makeRequest(string $endpoint, array $params = []): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . $endpoint, $params);

            if ($response->successful()) {
                return $this->parseResponse($response->json());
            }

            Log::error("{$this->getSourceName()} API request failed", ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error("{$this->getSourceName()} API exception", ['message' => $e->getMessage()]);
            return collect();
        }
    }

    abstract protected function parseResponse(array $data): Collection;
}
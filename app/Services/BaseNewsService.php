<?php

namespace App\Services;

use App\Services\Contracts\NewsSourceInterface;
use App\Services\Http\NewsApiClient;
use App\Services\Normalizers\NewsApiNormalizer;
use App\Services\Normalizers\GuardianNormalizer;
use App\Services\Normalizers\NYTimesNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\NewsApiException;

abstract class BaseNewsService implements NewsSourceInterface
{
    protected NewsApiClient $client;
    protected $normalizer;
    protected array $config;
    protected string $providerKey;

    public function __construct(string $providerKey)
    {
        $this->providerKey = $providerKey;
        $this->config = config("news.providers.{$providerKey}");

        if (!$this->config) {
            throw new NewsApiException("News provider '{$providerKey}' not configured");
        }

        $this->client = new NewsApiClient($this->config);
        $this->normalizer = $this->createNormalizer();
    }

    abstract protected function createNormalizer();

    public function fetchArticles(int $page = 1, array $options = []): Collection
    {
        $cacheKey = $this->getCacheKey("fetch_{$page}");

        return Cache::remember($cacheKey, config('news.cache.ttl', 1800), function () use ($page, $options) {
            try {
                $endpoint = $this->getFetchEndpoint();
                $params = $this->getFetchParams($page, $options);

                Log::info("Fetching articles from {$this->getSourceName()}", [
                    'page' => $page,
                    'endpoint' => $endpoint,
                    'params' => $params
                ]);

                $response = $this->client->get($endpoint, $params);
                return $this->normalizer->normalize($response);

            } catch (NewsApiException $e) {
                Log::error("Failed to fetch articles from {$this->getSourceName()}", [
                    'error' => $e->getMessage(),
                    'page' => $page
                ]);
                return collect();
            }
        });
    }

    public function searchArticles(string $query, int $page = 1, array $options = []): Collection
    {
        $cacheKey = $this->getCacheKey("search_{$query}_{$page}");

        return Cache::remember($cacheKey, config('news.cache.ttl', 1800), function () use ($query, $page, $options) {
            try {
                $endpoint = $this->getSearchEndpoint();
                $params = $this->getSearchParams($query, $page, $options);

                Log::info("Searching articles in {$this->getSourceName()}", [
                    'query' => $query,
                    'page' => $page,
                    'endpoint' => $endpoint
                ]);

                $response = $this->client->get($endpoint, $params);
                return $this->normalizer->normalize($response);

            } catch (NewsApiException $e) {
                Log::error("Failed to search articles in {$this->getSourceName()}", [
                    'error' => $e->getMessage(),
                    'query' => $query,
                    'page' => $page
                ]);
                return collect();
            }
        });
    }

    public function getSourceName(): string
    {
        return $this->config['name'];
    }

    public function getApiIdentifier(): string
    {
        return $this->providerKey;
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['base_url']);
    }

    protected function getCacheKey(string $suffix): string
    {
        return config('news.cache.prefix', 'news_api_') . $this->providerKey . '_' . $suffix;
    }

    abstract protected function getFetchEndpoint(): string;
    abstract protected function getSearchEndpoint(): string;
    abstract protected function getFetchParams(int $page, array $options): array;
    abstract protected function getSearchParams(string $query, int $page, array $options): array;
}

<?php

namespace App\Services;

use App\Services\Contracts\NewsSourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NYTimesService implements NewsSourceInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('news.providers.ny_times.api_key') ?? env('NYT_API_KEY');
        $this->baseUrl = config('news.providers.ny_times.base_url') ?? env('NYTIMES_BASE_URL');
    }

    public function fetchArticles(string $category = 'general', int $page = 1): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/topstories/v2/home.json', [
                'api-key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['response']['docs'] ?? []);
            }

            Log::error('NYTimes API request failed', ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error('NYTimes API exception', ['message' => $e->getMessage()]);
            return collect();
        }
    }

    public function searchArticles(string $query, int $page = 1): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/search/v2/articlesearch.json', [
                'api-key' => $this->apiKey,
                'q' => $query,
                'page' => $page,
                'sort' => 'newest'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['response']['docs'] ?? []);
            }

            Log::error('NYTimes search failed', ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error('NYTimes search exception', ['message' => $e->getMessage()]);
            return collect();
        }
    }

    public function getSourceName(): string
    {
        return 'New York Times';
    }

    public function getApiIdentifier(): string
    {
        return 'ny_times';
    }
}
<?php

namespace App\Services;

use App\Services\Contracts\NewsSourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsAPIService implements NewsSourceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://newsapi.org/v2';

    public function __construct()
    {
        $this->apiKey = config('services.news_api.key') ?? env('NEWS_API_KEY');
        $this->baseUrl = config('services.news_api.base_url') ?? env('NEWS_API_BASE_URL');
    }

    public function fetchArticles(string $category = 'general', int $page = 1): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/everything', [
                'apiKey' => $this->apiKey,
                'q' => $category,
                'page' => $page,
                'pageSize' => 50,
                'sortBy' => 'publishedAt',
                'language' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['articles'] ?? []);
            }

            Log::error('NewsAPI request failed', ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error('NewsAPI exception', ['message' => $e->getMessage()]);
            return collect();
        }
    }

    public function searchArticles(string $query, int $page = 1): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/everything', [
                'apiKey' => $this->apiKey,
                'q' => $query,
                'page' => $page,
                'pageSize' => 50,
                'sortBy' => 'publishedAt',
                'language' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['articles'] ?? []);
            }

            Log::error('NewsAPI search failed', ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error('NewsAPI search exception', ['message' => $e->getMessage()]);
            return collect();
        }
    }

    public function getSourceName(): string
    {
        return 'News API';
    }

    public function getApiIdentifier(): string
    {
        return 'news_api';
    }
}
<?php

namespace App\Services;

use App\Services\Contracts\NewsSourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuardianService implements NewsSourceInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.guardian.key') ?? env('GUARDIAN_API_KEY');
        $this->baseUrl = config('services.guardian.base_url') ?? env('GUARDIAN_BASE_URL');
    }

    public function fetchArticles(string $category = 'general', int $page = 1): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/search', [
                'api-key' => $this->apiKey,
                'section' => $category,
                'page' => $page,
                'page-size' => 50,
                'order-by' => 'newest',
                'show-fields' => 'headline,trailText,body,thumbnail'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['response']['results'] ?? []);
            }

            Log::error('Guardian API request failed', ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error('Guardian API exception', ['message' => $e->getMessage()]);
            return collect();
        }
    }

    public function searchArticles(string $query, int $page = 1): Collection
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/search', [
                'api-key' => $this->apiKey,
                'q' => $query,
                'page' => $page,
                'page-size' => 50,
                'order-by' => 'newest',
                'show-fields' => 'headline,trailText,body,thumbnail'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['response']['results'] ?? []);
            }

            Log::error('Guardian search failed', ['status' => $response->status()]);
            return collect();
        } catch (\Exception $e) {
            Log::error('Guardian search exception', ['message' => $e->getMessage()]);
            return collect();
        }
    }

    public function getSourceName(): string
    {
        return 'The Guardian';
    }

    public function getApiIdentifier(): string
    {
        return 'guardian';
    }
}
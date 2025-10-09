<?php

namespace App\Services\Normalizers;

use Illuminate\Support\Collection;

class NewsApiNormalizer
{
    public function normalize(Collection $response): Collection
    {
        if (!isset($response['articles'])) {
            return collect();
        }

        return collect($response['articles'])->map(function ($article) {
            return $this->normalizeArticle($article);
        });
    }

    private function normalizeArticle(array $article): array
    {
        return [
            'title' => $article['title'] ?? '',
            'description' => $article['description'] ?? '',
            'content' => $article['content'] ?? null,
            'url' => $article['url'] ?? '',
            'image_url' => $article['urlToImage'] ?? null,
            'published_at' => isset($article['publishedAt']) ? $this->parseDate($article['publishedAt']) : null,
            'source' => [
                'name' => $article['source']['name'] ?? null,
                'id' => $article['source']['id'] ?? null,
            ],
            'author' => $article['author'] ?? null,
            'category' => null, // NewsAPI doesn't provide category
        ];
    }

    private function parseDate(string $date): ?string
    {
        try {
            return date('Y-m-d H:i:s', strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }
}

<?php

namespace App\Services\Normalizers;

use Illuminate\Support\Collection;

class NYTimesNormalizer
{
    public function normalize(Collection $response): Collection
    {
        // Handle both top stories and search results
        $results = $response['results'] ?? $response['response']['docs'] ?? [];

        return collect($results)->map(function ($article) {
            return $this->normalizeArticle($article);
        });
    }

    private function normalizeArticle(array $article): array
    {
        // Handle different field structures for top stories vs search
        $title = $article['title'] ?? $article['headline']['main'] ?? '';
        $description = $article['abstract'] ?? null;
        $url = $article['url'] ?? $article['web_url'] ?? '';
        $publishedAt = $article['published_date'] ?? $article['pub_date'] ?? null;
        $author = $this->extractAuthor($article);
        $category = $article['section'] ?? $article['news_desk'] ?? null;

        return [
            'title' => $title,
            'description' => $description,
            'content' => null, // NYTimes API doesn't provide full content
            'url' => $url,
            'image_url' => $this->extractImage($article),
            'published_at' => $publishedAt ? $this->parseDate($publishedAt) : null,
            'source' => [
                'name' => 'New York Times',
                'id' => 'ny_times',
            ],
            'author' => $author,
            'category' => $category,
        ];
    }

    private function extractAuthor(array $article): ?string
    {
        if (isset($article['byline']['original'])) {
            $author = $article['byline']['original'];
            // Clean up "By " prefix
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }

        return $article['byline'] ?? null;
    }

    private function extractImage(array $article): ?string
    {
        if (isset($article['multimedia']) && is_array($article['multimedia']) && count($article['multimedia']) > 0) {
            return $article['multimedia'][0]['url'] ?? null;
        }

        return null;
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

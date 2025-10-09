<?php

namespace App\Services\Normalizers;

use Illuminate\Support\Collection;

class GuardianNormalizer
{
    public function normalize(Collection $response): Collection
    {
        $results = $response['response']['results'] ?? [];

        return collect($results)->map(function ($article) {
            return $this->normalizeArticle($article);
        });
    }

    private function normalizeArticle(array $article): array
    {
        $fields = $article['fields'] ?? [];

        return [
            'title' => $article['webTitle'] ?? '',
            'description' => $fields['trailText'] ?? null,
            'content' => $fields['body'] ?? null,
            'url' => $article['webUrl'] ?? '',
            'image_url' => $fields['thumbnail'] ?? null,
            'published_at' => isset($article['webPublicationDate']) ? $this->parseDate($article['webPublicationDate']) : null,
            'source' => [
                'name' => 'The Guardian',
                'id' => 'guardian',
            ],
            'author' => $fields['byline'] ?? null,
            'category' => $article['sectionName'] ?? null,
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

<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Source;
use App\Models\Category;
use App\Models\Author;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\Contracts\NewsSourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ArticleAggregatorService
{
    private ArticleRepositoryInterface $articleRepository;
    private array $newsServices;

    public function __construct(ArticleRepositoryInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
        $this->newsServices = [
            app(NewsAPIService::class),
            app(GuardianService::class),
            app(NYTimesService::class),
        ];
    }

    public function fetchAllArticles(string $category = 'general'): int
    {
        $totalFetched = 0;

        foreach ($this->newsServices as $service) {
            try {
                $articles = $service->fetchArticles($category);
                $processed = $this->processArticles($articles, $service);
                $totalFetched += $processed;
                
                Log::info("Fetched {$processed} articles from {$service->getSourceName()}");
            } catch (\Exception $e) {
                Log::error("Failed to fetch from {$service->getSourceName()}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $totalFetched;
    }

    private function processArticles(Collection $articles, NewsSourceInterface $service): int
    {
        $processed = 0;
        $source = $this->getOrCreateSource($service);

        foreach ($articles as $articleData) {
            if ($this->isDuplicate($articleData, $source)) {
                continue;
            }

            $article = $this->createArticle($articleData, $source);
            if ($article) {
                $processed++;
            }
        }

        return $processed;
    }

    private function getOrCreateSource(NewsSourceInterface $service): Source
    {
        return Source::firstOrCreate(
            ['api_identifier' => $service->getApiIdentifier()],
            [
                'name' => $service->getSourceName(),
                'slug' => \Str::slug($service->getSourceName()),
                'enabled' => true
            ]
        );
    }

    private function isDuplicate(array $articleData, Source $source): bool
    {
        $url = $this->extractUrl($articleData);
        return Article::where('url', $url)->exists();
    }

    private function createArticle(array $articleData, Source $source): ?Article
    {
        try {
            $article = Article::create([
                'title' => $this->extractTitle($articleData),
                'slug' => $this->generateSlug($this->extractTitle($articleData)),
                'description' => $this->extractDescription($articleData),
                'content' => $this->extractContent($articleData),
                'url' => $this->extractUrl($articleData),
                'image_url' => $this->extractImageUrl($articleData),
                'published_at' => $this->extractPublishedAt($articleData),
                'fetched_at' => now(),
                'source_id' => $source->id,
            ]);

            return $article;
        } catch (\Exception $e) {
            Log::error('Failed to create article', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractTitle(array $data): string
    {
        return $data['title'] ?? $data['headline'] ?? 'Untitled';
    }

    private function extractDescription(array $data): ?string
    {
        return $data['description'] ?? $data['trailText'] ?? null;
    }

    private function extractContent(array $data): ?string
    {
        return $data['content'] ?? $data['body'] ?? null;
    }

    private function extractUrl(array $data): string
    {
        return $data['url'] ?? $data['webUrl'] ?? '';
    }

    private function extractImageUrl(array $data): ?string
    {
        return $data['urlToImage'] ?? $data['thumbnail'] ?? null;
    }

    private function extractPublishedAt(array $data): ?string
    {
        $date = $data['publishedAt'] ?? $data['webPublicationDate'] ?? $data['pub_date'] ?? null;
        return $date ? \Carbon\Carbon::parse($date) : now();
    }

    private function generateSlug(string $title): string
    {
        return \Str::slug($title) . '-' . time();
    }
}
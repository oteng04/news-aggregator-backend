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

    public function fetchAllArticles(): int
    {
        $totalFetched = 0;

        foreach ($this->newsServices as $service) {
            try {
                $articles = $service->fetchArticles($category);
                $processed = $this->processArticles($articles, $service, $category);
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

    private function processArticles(Collection $articles, NewsSourceInterface $service, string $category = 'general'): int
    {
        $processed = 0;
        $source = $this->getOrCreateSource($service);

        foreach ($articles as $articleData) {
            if ($this->isDuplicate($articleData, $source)) {
                continue;
            }

            $article = $this->createArticle($articleData, $source, $category);
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

    private function getOrCreateAuthor(?string $authorName): Author
    {
        $name = empty($authorName) ? 'Unknown' : $authorName;
        return Author::firstOrCreate(['name' => $name]);
    }

    private function getOrCreateCategory(string $categoryName): Category
    {
        return Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'name' => $categoryName,
                'slug' => \Str::slug($categoryName),
                'description' => "Category: {$categoryName}"
            ]
        );
    }

    private function isDuplicate(array $articleData, Source $source): bool
    {
        $url = $this->extractUrl($articleData);
        return Article::where('url', $url)->exists();
    }

    private function createArticle(array $articleData, Source $source, string $category = 'general'): ?Article
    {
        try {
            $author = $this->getOrCreateAuthor($this->extractAuthor($articleData));
            $extractedCategory = $this->extractCategory($articleData);

            // For NewsAPI, use the passed category since it doesn't provide categories in response
            // For Guardian and NY Times, use the extracted category from the API response
            $finalCategory = ($extractedCategory !== 'General') ? $extractedCategory : $category;

            $categoryObj = $this->getOrCreateCategory($finalCategory);
            
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
                'author_id' => $author?->id,
                'category_id' => $category->id,
            ]);

            return $article;
        } catch (\Exception $e) {
            Log::error('Failed to create article', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractTitle(array $data): string
    {
        // NY Times API has nested headline structure
        if (isset($data['headline']) && is_array($data['headline'])) {
            return $data['headline']['main'] ?? 'Untitled';
        }

        return $data['title'] ?? $data['headline'] ?? $data['webTitle'] ?? 'Untitled';
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
        $date = $data['publishedAt'] ?? $data['webPublicationDate'] ?? $data['pub_date'] ?? $data['published_date'] ?? null;
        return $date ? \Carbon\Carbon::parse($date) : now();
    }

    private function extractAuthor(array $data): ?string
    {
        return $data['author'] ?? $data['fields']['byline'] ?? $data['byline']['original'] ?? 'Unknown';
    }
    
    private function extractCategory(array $data): string
    {
        // Guardian API uses 'sectionName'
        if (isset($data['sectionName'])) {
            return $data['sectionName'];
        }

        // NY Times API uses 'section'
        if (isset($data['section'])) {
            return $data['section'];
        }

        // NewsAPI doesn't provide category in response, so we use the passed parameter
        return $data['category'] ?? $data['pillarName'] ?? $data['section_name'] ?? 'General';
    }

    private function generateSlug(string $title): string
    {
        return \Str::slug($title) . '-' . time();
    }
}
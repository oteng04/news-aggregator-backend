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
        // Set up the three news sources we're pulling from
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
                $articles = $service->fetchArticles();
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
            // Skip if we already have this article (check by URL)
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

    private function getOrCreateRealSource(array $articleData, Source $fallbackSource): Source
    {
        // NewsAPI gives us real source names like "ABC News", use those instead of generic "News API"
        if (isset($articleData['source']['name']) && !empty($articleData['source']['name'])) {
            $realSourceName = $articleData['source']['name'];
            return Source::firstOrCreate(
                ['name' => $realSourceName],
                [
                    'name' => $realSourceName,
                    'slug' => \Str::slug($realSourceName),
                    'api_identifier' => $fallbackSource->api_identifier,
                    'enabled' => true
                ]
            );
        }


        return $fallbackSource;
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

    private function createArticle(array $articleData, Source $source): ?Article
    {
        try {
            // For NewsAPI, try to get the real publication name instead of generic "News API"
            $realSource = $this->getOrCreateRealSource($articleData, $source);
            $author = $this->getOrCreateAuthor($this->extractAuthor($articleData));
            $category = $this->getOrCreateCategory($this->extractCategory($articleData));
            
            $article = Article::create([
                'title' => $this->extractTitle($articleData),
                'slug' => $this->generateSlug($this->extractTitle($articleData)),
                'description' => $this->extractDescription($articleData),
                'content' => $this->extractContent($articleData),
                'url' => $this->extractUrl($articleData),
                'image_url' => $this->extractImageUrl($articleData),
                'published_at' => $this->extractPublishedAt($articleData),
                'fetched_at' => now(),
                'source_id' => $realSource->id,
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
        if (isset($data['title'])) {
            return $data['title'];
        }

        if (isset($data['headline']['main'])) {
            return $data['headline']['main'];
        }

        if (isset($data['webTitle'])) {
            return $data['webTitle'];
        }

        return 'Untitled';
    }

    private function extractDescription(array $data): ?string
    {
        if (isset($data['description'])) {
            return $data['description'];
        }

        if (isset($data['abstract'])) {
            return $data['abstract'];
        }

        if (isset($data['fields']['trailText'])) {
            return $data['fields']['trailText'];
        }

        return null;
    }

    private function extractContent(array $data): ?string
    {
        if (isset($data['content'])) {
            return $data['content'];
        }

        if (isset($data['fields']['body'])) {
            return $data['fields']['body'];
        }

        return null;
    }

    private function extractUrl(array $data): string
    {
        if (isset($data['url'])) {
            return $data['url'];
        }

        if (isset($data['webUrl'])) {
            return $data['webUrl'];
        }

        if (isset($data['web_url'])) {
            return $data['web_url'];
        }
        if (isset($data['url'])) {
            return $data['url'];
        }

        return '';
    }

    private function extractImageUrl(array $data): ?string
    {
        if (isset($data['urlToImage'])) {
            return $data['urlToImage'];
        }

        if (isset($data['fields']['thumbnail'])) {
            return $data['fields']['thumbnail'];
        }

        if (isset($data['multimedia']) && is_array($data['multimedia']) && count($data['multimedia']) > 0) {
            return $data['multimedia'][0]['url'] ?? null;
        }

        return null;
    }

    private function extractPublishedAt(array $data): ?string
    {
        if (isset($data['publishedAt'])) {
            return \Carbon\Carbon::parse($data['publishedAt']);
        }

        if (isset($data['webPublicationDate'])) {
            return \Carbon\Carbon::parse($data['webPublicationDate']);
        }

        if (isset($data['pub_date'])) {
            return \Carbon\Carbon::parse($data['pub_date']);
        }
        if (isset($data['published_date'])) {
            return \Carbon\Carbon::parse($data['published_date']);
        }

        return now();
    }

    private function extractAuthor(array $data): ?string
    {
        if (isset($data['author'])) {
            return $data['author'];
        }

        if (isset($data['fields']['byline'])) {
            return $data['fields']['byline'];
        }

        if (isset($data['byline']['original'])) {
            $author = $data['byline']['original'];
            // NY Times often prefixes authors with "By ", so clean that up
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }
        if (isset($data['byline'])) {
            $author = $data['byline'];
            // NY Times often prefixes authors with "By ", so clean that up
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }

        return 'Unknown';
    }
    
    private function extractCategory(array $data): string
    {
        if (isset($data['sectionName'])) {
            return $data['sectionName'];
        }

        if (isset($data['section'])) {
            return $data['section'];
        }
        if (isset($data['section_name'])) {
            return $data['section_name'];
        }

        return 'General';
    }

    private function generateSlug(string $title): string
    {
        // Add timestamp to avoid slug conflicts
        return \Str::slug($title) . '-' . time();
    }
}
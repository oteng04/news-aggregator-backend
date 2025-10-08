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

/**
 * Main service for aggregating news articles from multiple sources.
 * Handles the complexity of different API formats and normalizes them into our database.
 */
class ArticleAggregatorService
{
    private ArticleRepositoryInterface $articleRepository;
    private array $newsServices;

    public function __construct(ArticleRepositoryInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;

        // Set up our news sources - NewsAPI gives us broad coverage,
        // Guardian for UK-focused content, NY Times for US news
        $this->newsServices = [
            app(NewsAPIService::class),
            app(GuardianService::class),
            app(NYTimesService::class),
        ];
    }

    /**
     * Go through all our news services and fetch articles from each.
     * This is the main entry point when we want to update our database with fresh news.
     */
    public function fetchAllArticles(): int
    {
        $totalFetched = 0;

        // Hit up each news service one by one
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

    /**
     * For NewsAPI, we can get the actual publication name from the article data.
     * Instead of showing "News API" for everything, we show "ABC News", "BBC", etc.
     * Other services don't provide this level of detail so they use the fallback.
     */
    private function getOrCreateRealSource(array $articleData, Source $fallbackSource): Source
    {
        // NewsAPI gives us real source names in the article data
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

    /**
     * Take raw article data from an API and turn it into a proper Article record.
     * This handles all the messy differences between APIs - different field names,
     * different date formats, etc. It's like being a translator for 3 different languages.
     */
    private function createArticle(array $articleData, Source $source): ?Article
    {
        try {
            // NewsAPI gives us real publication names, others just use the service name
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

        // NY Times likes to prefix author names with "By " - let's clean that up
        if (isset($data['byline']['original'])) {
            $author = $data['byline']['original'];
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }
        if (isset($data['byline'])) {
            $author = $data['byline'];
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }

        return 'Unknown';
    }
    
    private function extractCategory(array $data): string
    {
        // Guardian gives us nice readable section names like "Sport", "Politics"
        if (isset($data['sectionName'])) {
            return $data['sectionName'];
        }

        // NY Times uses shorter codes, but they're still meaningful
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
        return \Str::slug($title) . '-' . time();
    }
}
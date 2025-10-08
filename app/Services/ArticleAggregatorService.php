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

    private function getOrCreateRealSource(array $articleData, Source $fallbackSource): Source
    {
        // NewsAPI provides real source names in article data
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

        // Guardian provides sectionName which could be used, but we'll stick with service source for now
        // NY Times doesn't provide individual source names in top stories

        // Fall back to the service source
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
            // Use real source name from article data if available (e.g., NewsAPI provides actual source names)
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
        // NewsAPI direct field
        if (isset($data['title'])) {
            return $data['title'];
        }

        // NY Times headline.main (search API) or title (top stories)
        if (isset($data['headline']['main'])) {
            return $data['headline']['main'];
        }

        // Guardian webTitle
        if (isset($data['webTitle'])) {
            return $data['webTitle'];
        }

        return 'Untitled';
    }

    private function extractDescription(array $data): ?string
    {
        // NewsAPI direct field
        if (isset($data['description'])) {
            return $data['description'];
        }

        // NY Times abstract
        if (isset($data['abstract'])) {
            return $data['abstract'];
        }

        // Guardian fields.trailText
        if (isset($data['fields']['trailText'])) {
            return $data['fields']['trailText'];
        }

        return null;
    }

    private function extractContent(array $data): ?string
    {
        // NewsAPI direct field
        if (isset($data['content'])) {
            return $data['content'];
        }

        // Guardian fields.body
        if (isset($data['fields']['body'])) {
            return $data['fields']['body'];
        }

        // NY Times doesn't provide full content in basic APIs
        return null;
    }

    private function extractUrl(array $data): string
    {
        // NewsAPI direct field
        if (isset($data['url'])) {
            return $data['url'];
        }

        // Guardian webUrl
        if (isset($data['webUrl'])) {
            return $data['webUrl'];
        }

        // NY Times web_url (search) or url (top stories)
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
        // NewsAPI urlToImage
        if (isset($data['urlToImage'])) {
            return $data['urlToImage'];
        }

        // Guardian fields.thumbnail
        if (isset($data['fields']['thumbnail'])) {
            return $data['fields']['thumbnail'];
        }

        // NY Times multimedia array
        if (isset($data['multimedia']) && is_array($data['multimedia']) && count($data['multimedia']) > 0) {
            return $data['multimedia'][0]['url'] ?? null;
        }

        return null;
    }

    private function extractPublishedAt(array $data): ?string
    {
        // NewsAPI publishedAt
        if (isset($data['publishedAt'])) {
            return \Carbon\Carbon::parse($data['publishedAt']);
        }

        // Guardian webPublicationDate
        if (isset($data['webPublicationDate'])) {
            return \Carbon\Carbon::parse($data['webPublicationDate']);
        }

        // NY Times pub_date (search) or published_date (top stories)
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
        // NewsAPI author
        if (isset($data['author'])) {
            return $data['author'];
        }

        // Guardian fields.byline
        if (isset($data['fields']['byline'])) {
            return $data['fields']['byline'];
        }

        // NY Times byline.original (search) or byline (top stories)
        if (isset($data['byline']['original'])) {
            $author = $data['byline']['original'];
            // Remove "By " prefix if present
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }
        if (isset($data['byline'])) {
            $author = $data['byline'];
            // Remove "By " prefix if present
            return str_starts_with($author, 'By ') ? substr($author, 3) : $author;
        }

        return 'Unknown';
    }
    
    private function extractCategory(array $data): string
    {
        // Guardian sectionName
        if (isset($data['sectionName'])) {
            return $data['sectionName'];
        }

        // NY Times section (top stories) or section_name (search)
        if (isset($data['section'])) {
            return $data['section'];
        }
        if (isset($data['section_name'])) {
            return $data['section_name'];
        }

        // NewsAPI doesn't provide category in response
        return 'General';
    }

    private function generateSlug(string $title): string
    {
        return \Str::slug($title) . '-' . time();
    }
}
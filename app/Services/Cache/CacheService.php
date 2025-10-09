<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache TTL configurations
     */
    private const CACHE_TTL = [
        'api_response' => 1800,    // 30 minutes
        'model' => 3600,           // 1 hour
        'query' => 1800,           // 30 minutes
        'config' => 86400,         // 24 hours
        'stats' => 300,            // 5 minutes
    ];

    /**
     * Cache tags for different types of data
     */
    private const CACHE_TAGS = [
        'articles' => 'articles',
        'sources' => 'sources',
        'categories' => 'categories',
        'authors' => 'authors',
        'api_responses' => 'api_responses',
        'stats' => 'stats',
    ];

    /**
     * Cache a model instance with automatic key generation
     */
    public function rememberModel(Model $model, ?int $ttl = null): Model
    {
        $key = $this->generateModelKey($model);
        $ttl = $ttl ?? self::CACHE_TTL['model'];

        return Cache::tags([self::CACHE_TAGS[$model->getTable()]])
            ->remember($key, $ttl, function () use ($model) {
                return $model;
            });
    }

    /**
     * Cache a collection of models
     */
    public function rememberCollection(Collection $collection, string $key, ?int $ttl = null): Collection
    {
        $ttl = $ttl ?? self::CACHE_TTL['query'];

        return Cache::remember($key, $ttl, function () use ($collection) {
            return $collection;
        });
    }

    /**
     * Cache a paginated result
     */
    public function rememberPaginator(LengthAwarePaginator $paginator, string $key, ?int $ttl = null): LengthAwarePaginator
    {
        $ttl = $ttl ?? self::CACHE_TTL['query'];

        return Cache::remember($key, $ttl, function () use ($paginator) {
            return $paginator;
        });
    }

    /**
     * Cache API responses with smart key generation
     */
    public function rememberApiResponse(string $endpoint, array $params, callable $callback, ?int $ttl = null): mixed
    {
        $key = $this->generateApiKey($endpoint, $params);
        $ttl = $ttl ?? self::CACHE_TTL['api_response'];

        return Cache::tags([self::CACHE_TAGS['api_responses']])
            ->remember($key, $ttl, function () use ($callback) {
                $startTime = microtime(true);
                $result = $callback();
                $duration = round((microtime(true) - $startTime) * 1000, 2);

                // Log cache miss with performance metrics
                \Illuminate\Support\Facades\Log::info('Cache miss for API response', [
                    'duration_ms' => $duration,
                    'timestamp' => now()->toISOString(),
                ]);

                return $result;
            });
    }

    /**
     * Cache statistics data
     */
    public function rememberStats(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::CACHE_TTL['stats'];

        return Cache::tags([self::CACHE_TAGS['stats']])
            ->remember("stats:{$key}", $ttl, $callback);
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Invalidate model cache when model is updated
     */
    public function invalidateModelCache(Model $model): void
    {
        $table = $model->getTable();
        $key = $this->generateModelKey($model);

        Cache::tags([self::CACHE_TAGS[$table]])->forget($key);

        // Also invalidate related caches
        $this->invalidateRelatedCaches($model);
    }

    /**
     * Invalidate all caches for a table
     */
    public function invalidateTableCache(string $table): void
    {
        if (isset(self::CACHE_TAGS[$table])) {
            Cache::tags([self::CACHE_TAGS[$table]])->flush();
        }
    }

    /**
     * Warm up caches by pre-loading frequently accessed data
     */
    public function warmUpCaches(): array
    {
        $results = [];

        // Warm up basic stats
        $results['stats'] = $this->warmUpStatsCache();

        // Warm up common API responses
        $results['api'] = $this->warmUpApiCache();

        // Warm up model caches
        $results['models'] = $this->warmUpModelCache();

        return $results;
    }

    /**
     * Get cache statistics and hit rates
     */
    public function getCacheStats(): array
    {
        try {
            $info = Redis::info('stats');

            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate(
                    $info['keyspace_hits'] ?? 0,
                    $info['keyspace_misses'] ?? 0
                ),
                'total_keys' => Redis::dbSize(),
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'uptime_days' => round(($info['uptime_in_seconds'] ?? 0) / 86400, 1),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear all application caches
     */
    public function clearAllCaches(): array
    {
        $results = [];

        // Clear Laravel caches
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        $results['cache'] = 'cleared';

        \Illuminate\Support\Facades\Artisan::call('config:clear');
        $results['config'] = 'cleared';

        \Illuminate\Support\Facades\Artisan::call('route:clear');
        $results['route'] = 'cleared';

        \Illuminate\Support\Facades\Artisan::call('view:clear');
        $results['view'] = 'cleared';

        // Clear Redis caches
        Cache::flush();
        $results['redis'] = 'flushed';

        return $results;
    }

    // Private helper methods

    private function generateModelKey(Model $model): string
    {
        return sprintf(
            '%s:%s:%s',
            $model->getTable(),
            $model->getKey(),
            $model->updated_at?->timestamp ?? time()
        );
    }

    private function generateApiKey(string $endpoint, array $params): string
    {
        $paramString = http_build_query($params);
        return sprintf('api:%s:%s', $endpoint, md5($paramString));
    }

    private function invalidateRelatedCaches(Model $model): void
    {
        // Invalidate article-related caches when source/category changes
        if ($model instanceof \App\Models\Source || $model instanceof \App\Models\Category) {
            Cache::tags([self::CACHE_TAGS['articles']])->flush();
        }

        // Invalidate author-related caches when author changes
        if ($model instanceof \App\Models\Author) {
            Cache::tags([self::CACHE_TAGS['articles']])->flush();
        }
    }

    private function warmUpStatsCache(): array
    {
        $stats = [
            'total_articles' => \App\Models\Article::count(),
            'total_sources' => \App\Models\Source::count(),
            'total_categories' => \App\Models\Category::count(),
            'total_authors' => \App\Models\Author::count(),
        ];

        foreach ($stats as $key => $value) {
            $this->rememberStats($key, fn() => $value);
        }

        return $stats;
    }

    private function warmUpApiCache(): array
    {
        $results = [];

        // Cache common API responses
        try {
            $this->rememberApiResponse('articles', ['page' => 1, 'per_page' => 20], function () {
                return app(\App\Repositories\Contracts\ArticleRepositoryInterface::class)
                    ->getPaginated(20);
            });
            $results['articles'] = 'cached';
        } catch (\Exception $e) {
            $results['articles'] = 'error: ' . $e->getMessage();
        }

        try {
            $this->rememberApiResponse('sources', [], function () {
                return \App\Models\Source::where('enabled', true)->get();
            });
            $results['sources'] = 'cached';
        } catch (\Exception $e) {
            $results['sources'] = 'error: ' . $e->getMessage();
        }

        return $results;
    }

    private function warmUpModelCache(): array
    {
        $results = [];

        try {
            // Cache recently accessed articles
            $recentArticles = \App\Models\Article::latest()->take(10)->get();
            foreach ($recentArticles as $article) {
                $this->rememberModel($article);
            }
            $results['recent_articles'] = count($recentArticles);
        } catch (\Exception $e) {
            $results['recent_articles'] = 'error: ' . $e->getMessage();
        }

        return $results;
    }

    private function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }
}

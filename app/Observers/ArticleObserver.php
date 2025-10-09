<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Cache\CacheService;

class ArticleObserver
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        $this->invalidateArticleCaches();
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        $this->cacheService->invalidateModelCache($article);
        $this->invalidateArticleCaches();
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        $this->cacheService->invalidateModelCache($article);
        $this->invalidateArticleCaches();
    }

    /**
     * Handle the Article "restored" event.
     */
    public function restored(Article $article): void
    {
        $this->invalidateArticleCaches();
    }

    /**
     * Handle the Article "force deleted" event.
     */
    public function forceDeleted(Article $article): void
    {
        $this->cacheService->invalidateModelCache($article);
        $this->invalidateArticleCaches();
    }

    /**
     * Invalidate all article-related caches
     */
    private function invalidateArticleCaches(): void
    {
        // Invalidate article collection caches
        $this->cacheService->invalidateTags(['articles', 'stats']);

        // Clear specific API response caches
        \Illuminate\Support\Facades\Cache::forget('api:articles:*');
        \Illuminate\Support\Facades\Cache::forget('api:articles/search:*');
    }
}

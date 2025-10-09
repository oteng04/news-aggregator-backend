<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cache\CacheService;

class CategoryObserver
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function created(Category $category): void
    {
        $this->invalidateCategoryCaches();
    }

    public function updated(Category $category): void
    {
        $this->cacheService->invalidateModelCache($category);
        $this->invalidateCategoryCaches();
    }

    public function deleted(Category $category): void
    {
        $this->cacheService->invalidateModelCache($category);
        $this->invalidateCategoryCaches();
    }

    public function restored(Category $category): void
    {
        $this->invalidateCategoryCaches();
    }

    public function forceDeleted(Category $category): void
    {
        $this->cacheService->invalidateModelCache($category);
        $this->invalidateCategoryCaches();
    }

    private function invalidateCategoryCaches(): void
    {
        $this->cacheService->invalidateTags(['categories', 'articles', 'stats']);
        \Illuminate\Support\Facades\Cache::forget('api:categories:*');
    }
}

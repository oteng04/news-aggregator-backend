<?php

namespace App\Observers;

use App\Models\Source;
use App\Services\Cache\CacheService;

class SourceObserver
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function created(Source $source): void
    {
        $this->invalidateSourceCaches();
    }

    public function updated(Source $source): void
    {
        $this->cacheService->invalidateModelCache($source);
        $this->invalidateSourceCaches();
    }

    public function deleted(Source $source): void
    {
        $this->cacheService->invalidateModelCache($source);
        $this->invalidateSourceCaches();
    }

    public function restored(Source $source): void
    {
        $this->invalidateSourceCaches();
    }

    public function forceDeleted(Source $source): void
    {
        $this->cacheService->invalidateModelCache($source);
        $this->invalidateSourceCaches();
    }

    private function invalidateSourceCaches(): void
    {
        $this->cacheService->invalidateTags(['sources', 'articles', 'stats']);
        \Illuminate\Support\Facades\Cache::forget('api:sources:*');
    }
}

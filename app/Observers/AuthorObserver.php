<?php

namespace App\Observers;

use App\Models\Author;
use App\Services\Cache\CacheService;

class AuthorObserver
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function created(Author $author): void
    {
        $this->invalidateAuthorCaches();
    }

    public function updated(Author $author): void
    {
        $this->cacheService->invalidateModelCache($author);
        $this->invalidateAuthorCaches();
    }

    public function deleted(Author $author): void
    {
        $this->cacheService->invalidateModelCache($author);
        $this->invalidateAuthorCaches();
    }

    public function restored(Author $author): void
    {
        $this->invalidateAuthorCaches();
    }

    public function forceDeleted(Author $author): void
    {
        $this->cacheService->invalidateModelCache($author);
        $this->invalidateAuthorCaches();
    }

    private function invalidateAuthorCaches(): void
    {
        $this->cacheService->invalidateTags(['authors', 'articles', 'stats']);
        \Illuminate\Support\Facades\Cache::forget('api:authors:*');
    }
}

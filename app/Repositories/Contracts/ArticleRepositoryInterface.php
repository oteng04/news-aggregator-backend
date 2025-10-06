<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ArticleRepositoryInterface
{
    public function getAll(): Collection;
    
    public function getPaginated(int $perPage = 20): LengthAwarePaginator;
    
    public function findBySlug(string $slug): ?object;
    
    public function search(string $query, array $filters = []): LengthAwarePaginator;
    
    public function getBySource(int $sourceId): Collection;
    
    public function getByCategory(int $categoryId): Collection;
}
<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ArticleRepositoryInterface
{
    public function getAll(): Collection;
    
    public function getPaginated(int $perPage = 20): LengthAwarePaginator;
    
    public function findBySlug(string $slug): ?object;
    
    public function search(string $query, array $filters = []): LengthAwarePaginator;
    
    public function getBySource(int $sourceId): Collection;
    
    public function getByCategory(int $categoryId): Collection;
    
    public function all(): Collection;
    
    public function find(int $id): ?object;
    
    public function findOrFail(int $id): ?object;
    
    public function create(array $data): object;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function paginate(): LengthAwarePaginator;
    
    public function getLatestArticles(array $filters = []): LengthAwarePaginator;
    
    public function updateOrCreate(array $attributes, array $data): object;
    
    public function upsert(array $values, array $uniqueBy, array $update): int;
}
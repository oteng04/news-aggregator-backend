<?php

namespace App\Repositories;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function getAll(): Collection
    {
        return Article::with(['source', 'category', 'authors'])->get();
    }

    public function getPaginated(int $perPage = 20): LengthAwarePaginator
    {
        return Article::with(['source', 'category', 'authors'])
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function findBySlug(string $slug): ?object
    {
        return Article::with(['source', 'category', 'authors'])
            ->where('slug', $slug)
            ->first();
    }

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $articles = Article::with(['source', 'category', 'authors'])
            // Search across title, description, and content fields
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            });

        // Apply optional filters for source and category
        if (isset($filters['source_id'])) {
            $articles->where('source_id', $filters['source_id']);
        }

        if (isset($filters['category_id'])) {
            $articles->where('category_id', $filters['category_id']);
        }

        return $articles->orderBy('published_at', 'desc')->paginate(20);
    }

    public function getBySource(int $sourceId): Collection
    {
        return Article::with(['source', 'category', 'authors'])
            ->where('source_id', $sourceId)
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return Article::with(['source', 'category', 'authors'])
            ->where('category_id', $categoryId)
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function all(): Collection
    {
        return $this->getAll();
    }

    public function find(int $id): ?object
    {
        return Article::with(['source', 'category', 'authors'])->find($id);
    }

    public function findOrFail(int $id): ?object
    {
        return Article::with(['source', 'category', 'authors'])->findOrFail($id);
    }

    public function create(array $data): object
    {
        return Article::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Article::where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return Article::destroy($id) > 0;
    }

    public function paginate(): LengthAwarePaginator
    {
        return $this->getPaginated();
    }

    public function getLatestArticles(array $filters = []): LengthAwarePaginator
    {
        $query = Article::with(['source', 'category', 'authors'])
            ->orderBy('published_at', 'desc');

        if (isset($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->paginate(20);
    }

    public function updateOrCreate(array $attributes, array $data): object
    {
        return Article::updateOrCreate($attributes, $data);
    }

    public function upsert(array $values, array $uniqueBy, array $update): int
    {
        return Article::upsert($values, $uniqueBy, $update);
    }
}
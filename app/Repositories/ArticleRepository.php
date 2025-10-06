<?php

namespace App\Repositories;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            });

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
}
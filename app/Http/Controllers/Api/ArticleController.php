<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ArticleIndexRequest;
use App\Http\Requests\Api\ArticleSearchRequest;
use App\Http\Resources\ApiResponse;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository
    ) {}

    public function index(ArticleIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Build query filters
        $filters = [];
        if (isset($validated['source_id'])) {
            $filters['source_id'] = $validated['source_id'];
        }
        if (isset($validated['category_id'])) {
            $filters['category_id'] = $validated['category_id'];
        }

        // Get paginated articles with filters
        if (!empty($filters)) {
            $articles = $this->articleRepository->getPaginatedWithFilters(
                $validated['per_page'],
                $filters
            );
        } else {
            $articles = $this->articleRepository->getPaginated($validated['per_page']);
        }

        // Add request metadata
        $meta = [
            'request_params' => [
                'page' => $validated['page'],
                'per_page' => $validated['per_page'],
                'filters' => $filters,
            ],
            'execution_time' => round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms',
        ];

        return ApiResponse::paginated($articles, 'articles', $meta);
    }

    public function show(string $slug): JsonResponse
    {
        $article = $this->articleRepository->findBySlug($slug);

        if (!$article) {
            return ApiResponse::notFound('Article', $slug);
        }

        $meta = [
            'execution_time' => round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms',
        ];

        return ApiResponse::success($article, 'Article retrieved successfully', $meta);
    }

    public function search(ArticleSearchRequest $request): JsonResponse
    {
        $searchParams = $request->getSearchParameters();

        // Perform search using repository
        $articles = $this->articleRepository->search(
            $searchParams['query'],
            $searchParams['filters']
        );

        // Add search-specific metadata
        $meta = [
            'query' => $searchParams['query'],
            'filters_applied' => array_filter($searchParams['filters']),
            'execution_time' => round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms',
            'search_engine' => 'database', // Could be 'elasticsearch' in future
        ];

        return ApiResponse::paginated($articles, 'articles', $meta);
    }
}
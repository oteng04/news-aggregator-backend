<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::select(['id', 'name', 'slug', 'description', 'created_at'])
            ->orderBy('name')
            ->get();

        $meta = [
            'total_categories' => $categories->count(),
            'execution_time' => round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms',
        ];

        return ApiResponse::success(
            $categories,
            'Categories retrieved successfully',
            $meta
        );
    }
}
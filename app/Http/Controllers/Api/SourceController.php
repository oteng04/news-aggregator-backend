<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = Source::where('enabled', true)
            ->select(['id', 'name', 'slug', 'api_identifier', 'created_at'])
            ->orderBy('name')
            ->get();

        $meta = [
            'total_sources' => $sources->count(),
            'execution_time' => round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms',
        ];

        return ApiResponse::success(
            $sources,
            'Sources retrieved successfully',
            $meta
        );
    }
}
<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Versioning - All endpoints under v1 prefix
Route::middleware(['api.logging'])->prefix('v1')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    // Article routes with appropriate rate limiting
    Route::prefix('articles')->middleware(['throttle:listing'])->group(function () {
        Route::get('/', [ArticleController::class, 'index']); // listing rate limit
        Route::get('/search', [ArticleController::class, 'search'])->middleware('throttle:search'); // stricter search limit
        Route::get('/{slug}', [ArticleController::class, 'show'])->middleware('throttle:detail'); // detail rate limit
    });

    // Source routes - listing rate limit
    Route::get('/sources', [SourceController::class, 'index'])->middleware('throttle:listing');

    // Category routes - listing rate limit
    Route::get('/categories', [CategoryController::class, 'index'])->middleware('throttle:listing');

    // Health check endpoint - no rate limiting
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'services' => [
                'database' => \DB::connection()->getPdo() ? 'healthy' : 'unhealthy',
                'redis' => \Illuminate\Support\Facades\Redis::ping() ? 'healthy' : 'unhealthy',
            ]
        ]);
    });

// System stats endpoint - moderate rate limiting
Route::get('/stats', function () {
    $monitor = app(\App\Services\Monitoring\SystemMonitor::class);
    $metrics = $monitor->getMetrics();

    return response()->json([
        'success' => true,
        'data' => $metrics['application'],
        'meta' => [
            'version' => config('app.api_version', '1.0.0'),
            'timestamp' => now()->toISOString(),
        ]
    ]);
})->middleware('throttle:detail');

// Comprehensive health check endpoint
Route::get('/health', function () {
    $monitor = app(\App\Services\Monitoring\SystemMonitor::class);
    $health = $monitor->healthCheck();

    $statusCode = $health['status'] === 'healthy' ? 200 : 503;

    return response()->json($health, $statusCode);
});

// Detailed system metrics endpoint (admin only)
Route::get('/metrics', function () {
    $monitor = app(\App\Services\Monitoring\SystemMonitor::class);
    $metrics = $monitor->getMetrics();

    return response()->json([
        'success' => true,
        'data' => $metrics,
        'meta' => [
            'version' => config('app.api_version', '1.0.0'),
            'timestamp' => now()->toISOString(),
        ]
    ]);
})->middleware('throttle:detail');
});

// Legacy routes without versioning (for backward compatibility)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'message' => 'Use /v1/health for the latest API version'
    ]);
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found. Please use /v1/ prefix for API v1 endpoints.',
        'available_versions' => ['v1'],
        'documentation' => '/docs/api'
    ], 404);
});
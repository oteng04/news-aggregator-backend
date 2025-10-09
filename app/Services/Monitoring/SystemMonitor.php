<?php

namespace App\Services\Monitoring;

use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemMonitor
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Perform comprehensive health check
     */
    public function healthCheck(): array
    {
        $startTime = microtime(true);

        $checks = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'cache' => $this->checkCache(),
                'external_apis' => $this->checkExternalApis(),
                'storage' => $this->checkStorage(),
                'queue' => $this->checkQueue(),
            ]
        ];

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $checks['response_time_ms'] = $duration;

        // Determine overall status
        $failedChecks = array_filter($checks['checks'], fn($check) => $check['status'] !== 'healthy');
        if (!empty($failedChecks)) {
            $checks['status'] = 'unhealthy';
        }

        // Log health check results
        if ($checks['status'] === 'healthy') {
            Log::info('Health check passed', ['duration_ms' => $duration]);
        } else {
            Log::warning('Health check failed', [
                'duration_ms' => $duration,
                'failed_checks' => array_keys($failedChecks)
            ]);
        }

        return $checks;
    }

    /**
     * Get detailed system metrics
     */
    public function getMetrics(): array
    {
        return [
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'performance' => $this->getPerformanceMetrics(),
            'cache' => $this->cacheService->getCacheStats(),
            'database' => $this->getDatabaseMetrics(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get system resource metrics
     */
    private function getSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'uptime_seconds' => time() - LARAVEL_START,
        ];
    }

    /**
     * Get application-specific metrics
     */
    private function getApplicationMetrics(): array
    {
        return [
            'total_articles' => $this->cacheService->rememberStats('total_articles', fn() => \App\Models\Article::count()),
            'total_sources' => $this->cacheService->rememberStats('total_sources', fn() => \App\Models\Source::count()),
            'total_categories' => $this->cacheService->rememberStats('total_categories', fn() => \App\Models\Category::count()),
            'total_authors' => $this->cacheService->rememberStats('total_authors', fn() => \App\Models\Author::count()),
            'articles_today' => \App\Models\Article::whereDate('created_at', today())->count(),
            'active_sources' => \App\Models\Source::where('enabled', true)->count(),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        $metrics = [
            'average_response_time' => $this->calculateAverageResponseTime(),
            'requests_per_minute' => $this->getRequestsPerMinute(),
            'error_rate' => $this->calculateErrorRate(),
            'throughput' => $this->getThroughput(),
        ];

        // Add cache hit rate from cache service
        $cacheStats = $this->cacheService->getCacheStats();
        $metrics['cache_hit_rate'] = $cacheStats['hit_rate'] ?? 0;

        return $metrics;
    }

    /**
     * Get database metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $metrics = DB::select("
                SELECT
                    SUM(data_length + index_length) as size_bytes,
                    COUNT(*) as table_count
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);

            $connections = DB::select("SHOW PROCESSLIST");

            return [
                'status' => 'healthy',
                'size_mb' => round(($metrics[0]->size_bytes ?? 0) / 1024 / 1024, 2),
                'table_count' => $metrics[0]->table_count ?? 0,
                'active_connections' => count($connections),
                'connection_pool_size' => config('database.connections.mysql.options.100') ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $result = DB::select('SELECT 1');

            return [
                'status' => 'healthy',
                'response_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
                'connection_count' => DB::getConnections()[0]?->getTotalCount() ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            $startTime = microtime(true);
            $isConnected = \Illuminate\Support\Facades\Redis::ping();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($isConnected) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'version' => \Illuminate\Support\Facades\Redis::info()['redis_version'] ?? 'unknown',
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Redis ping failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test_value', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            if ($value === 'test_value') {
                return [
                    'status' => 'healthy',
                    'driver' => config('cache.default'),
                    'ttl_seconds' => 10,
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Cache read/write test failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ];
        }
    }

    /**
     * Check external API connectivity
     */
    private function checkExternalApis(): array
    {
        $apis = [
            'newsapi' => 'https://newsapi.org/v2/top-headlines?country=us&apiKey=' . config('news.providers.news_api.api_key'),
            'guardian' => 'https://content.guardianapis.com/search?api-key=' . config('news.providers.guardian.api_key'),
            'nytimes' => 'https://api.nytimes.com/svc/topstories/v2/home.json?api-key=' . config('news.providers.ny_times.api_key'),
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($apis as $name => $url) {
            try {
                $startTime = microtime(true);
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $results[$name] = [
                        'status' => 'healthy',
                        'response_time_ms' => $responseTime,
                        'status_code' => $response->status(),
                    ];
                    $healthyCount++;
                } else {
                    $results[$name] = [
                        'status' => 'unhealthy',
                        'response_time_ms' => $responseTime,
                        'status_code' => $response->status(),
                        'error' => 'Non-2xx response',
                    ];
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                ];
            }
        }

        return [
            'status' => $healthyCount === count($apis) ? 'healthy' : 'degraded',
            'healthy_count' => $healthyCount,
            'total_count' => count($apis),
            'apis' => $results,
        ];
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check test';

            \Illuminate\Support\Facades\Storage::put($testFile, $testContent);
            $readContent = \Illuminate\Support\Facades\Storage::get($testFile);
            \Illuminate\Support\Facades\Storage::delete($testFile);

            if ($readContent === $testContent) {
                return [
                    'status' => 'healthy',
                    'disk' => config('filesystems.default'),
                    'test_file' => $testFile,
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Storage read/write test failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ];
        }
    }

    /**
     * Check queue system
     */
    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = \Illuminate\Support\Facades\Queue::size();

            return [
                'status' => 'healthy',
                'connection' => $connection,
                'queue_size' => $size,
                'failed_jobs' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ];
        }
    }

    /**
     * Calculate average response time (simplified)
     */
    private function calculateAverageResponseTime(): float
    {
        // This would typically come from logs or APM
        // For demo purposes, return a mock value
        return rand(50, 200);
    }

    /**
     * Get requests per minute (simplified)
     */
    private function getRequestsPerMinute(): int
    {
        // This would typically come from logs or APM
        // For demo purposes, return a mock value
        return rand(10, 100);
    }

    /**
     * Calculate error rate (simplified)
     */
    private function calculateErrorRate(): float
    {
        // This would typically come from logs or APM
        // For demo purposes, return a mock value
        return round(rand(0, 500) / 100, 2);
    }

    /**
     * Get system throughput (simplified)
     */
    private function getThroughput(): int
    {
        // This would typically come from logs or APM
        // For demo purposes, return a mock value
        return rand(50, 200);
    }
}

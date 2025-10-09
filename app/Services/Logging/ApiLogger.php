<?php

namespace App\Services\Logging;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    /**
     * Log an incoming API request
     */
    public function logRequest(Request $request): void
    {
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'api_version' => $request->segment(1) === 'v1' ? 'v1' : 'legacy',
            'endpoint' => $request->route()?->getName() ?? 'unknown',
            'params' => $this->sanitizeParams($request->all()),
            'timestamp' => now()->toISOString(),
            'request_id' => $request->header('X-Request-ID', uniqid('req_', true)),
        ];

        // Store request ID for response logging
        $request->merge(['_request_id' => $context['request_id']]);

        Log::info('API Request', $context);
    }

    /**
     * Log an API response
     */
    public function logResponse(Request $request, JsonResponse $response): void
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
            'request_id' => $request->get('_request_id', 'unknown'),
            'endpoint' => $request->route()?->getName() ?? 'unknown',
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        // Log based on response status
        if ($response->isSuccessful()) {
            Log::info('API Response Success', $context);
        } elseif ($response->isClientError()) {
            Log::warning('API Response Client Error', array_merge($context, [
                'response_data' => $response->getData(true)
            ]));
        } elseif ($response->isServerError()) {
            Log::error('API Response Server Error', array_merge($context, [
                'response_data' => $response->getData(true)
            ]));
        }
    }

    /**
     * Log API errors with detailed context
     */
    public function logError(Request $request, \Throwable $exception, array $additionalContext = []): void
    {
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->get('_request_id', 'unknown'),
            'endpoint' => $request->route()?->getName() ?? 'unknown',
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'request_params' => $this->sanitizeParams($request->all()),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'timestamp' => now()->toISOString(),
        ];

        // Merge additional context
        $context = array_merge($context, $additionalContext);

        Log::error('API Error', $context);
    }

    /**
     * Log performance metrics for API endpoints
     */
    public function logPerformance(Request $request, float $duration, array $metrics = []): void
    {
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'endpoint' => $request->route()?->getName() ?? 'unknown',
            'duration_ms' => $duration,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'request_id' => $request->get('_request_id', 'unknown'),
            'timestamp' => now()->toISOString(),
        ];

        // Add custom metrics
        $context = array_merge($context, $metrics);

        // Log performance warnings for slow requests
        if ($duration > 1000) { // More than 1 second
            Log::warning('Slow API Request', $context);
        } else {
            Log::info('API Performance', $context);
        }
    }

    /**
     * Log cache hits/misses
     */
    public function logCacheOperation(string $operation, string $key, bool $hit, float $duration = 0): void
    {
        $context = [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString(),
        ];

        if ($operation === 'miss') {
            Log::info('Cache Miss', $context);
        } else {
            Log::debug('Cache Hit', $context);
        }
    }

    /**
     * Log rate limiting events
     */
    public function logRateLimit(Request $request, string $limiter, int $remaining): void
    {
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'limiter' => $limiter,
            'remaining' => $remaining,
            'request_id' => $request->get('_request_id', 'unknown'),
            'timestamp' => now()->toISOString(),
        ];

        if ($remaining === 0) {
            Log::warning('Rate Limit Exceeded', $context);
        } else {
            Log::debug('Rate Limit Check', $context);
        }
    }

    /**
     * Sanitize sensitive parameters for logging
     */
    private function sanitizeParams(array $params): array
    {
        $sensitiveKeys = ['password', 'token', 'key', 'secret', 'api_key'];

        $sanitized = [];
        foreach ($params as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeParams($value);
            } elseif (is_string($value) && strlen($value) > 100) {
                $sanitized[$key] = substr($value, 0, 100) . '...';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize headers for logging
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];

        $sanitized = [];
        foreach ($headers as $key => $values) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $sanitized[$key] = ['[REDACTED]'];
            } else {
                $sanitized[$key] = $values;
            }
        }

        return $sanitized;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Rate limiting configuration for different endpoint types
     */
    private const RATE_LIMITS = [
        'search' => [
            'max_attempts' => 30,  // 30 requests per window
            'decay_seconds' => 60, // per minute
            'description' => 'Search endpoints are limited to 30 requests per minute'
        ],
        'listing' => [
            'max_attempts' => 100, // 100 requests per window
            'decay_seconds' => 60, // per minute
            'description' => 'Listing endpoints are limited to 100 requests per minute'
        ],
        'detail' => [
            'max_attempts' => 200, // 200 requests per window
            'decay_seconds' => 60, // per minute
            'description' => 'Detail endpoints are limited to 200 requests per minute'
        ],
        'default' => [
            'max_attempts' => 50,  // 50 requests per window
            'decay_seconds' => 60, // per minute
            'description' => 'Default rate limit is 50 requests per minute'
        ],
    ];

    public function handle(Request $request, Closure $next, ?string $type = null): Response
    {
        // Determine rate limit type based on request
        $limitType = $type ?? $this->determineLimitType($request);

        $config = self::RATE_LIMITS[$limitType] ?? self::RATE_LIMITS['default'];

        // Create a unique key for this user/IP and endpoint type
        $key = $this->generateRateLimitKey($request, $limitType);

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, $config['max_attempts'])) {
            $availableIn = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'details' => [
                    'limit_type' => $limitType,
                    'max_attempts' => $config['max_attempts'],
                    'window_seconds' => $config['decay_seconds'],
                    'available_in_seconds' => $availableIn,
                    'description' => $config['description']
                ],
                'timestamp' => now()->toISOString(),
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => $availableIn,
                'X-RateLimit-Limit' => $config['max_attempts'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($availableIn)->timestamp,
            ]);
        }

        // Record the request
        RateLimiter::hit($key, $config['decay_seconds']);

        // Add rate limit headers to successful response
        $response = $next($request);

        if ($response instanceof Response) {
            $remaining = RateLimiter::remaining($key, $config['max_attempts']);
            $response->headers->set('X-RateLimit-Limit', $config['max_attempts']);
            $response->headers->set('X-RateLimit-Remaining', $remaining);
            $response->headers->set('X-RateLimit-Reset', now()->addSeconds(RateLimiter::availableIn($key))->timestamp);
        }

        return $response;
    }

    /**
     * Determine the rate limit type based on the request
     */
    private function determineLimitType(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();

        // Search endpoints (most expensive)
        if (str_contains($path, '/search') || $request->has('q')) {
            return 'search';
        }

        // Detail endpoints (single resource access)
        if (preg_match('/\/\d+$/', $path) && $method === 'GET') {
            return 'detail';
        }

        // Listing endpoints (collection access)
        if ($method === 'GET' && !preg_match('/\/\d+$/', $path)) {
            return 'listing';
        }

        return 'default';
    }

    /**
     * Generate a unique rate limit key
     */
    private function generateRateLimitKey(Request $request, string $limitType): string
    {
        $identifier = $this->getRequestIdentifier($request);

        return "rate_limit:{$limitType}:{$identifier}:" . md5($request->path());
    }

    /**
     * Get a unique identifier for the request (IP, user, or API key)
     */
    private function getRequestIdentifier(Request $request): string
    {
        // Check for API key in header
        if ($request->hasHeader('X-API-Key')) {
            return 'api_key:' . hash('sha256', $request->header('X-API-Key'));
        }

        // Check for authenticated user
        if ($request->user()) {
            return 'user:' . $request->user()->id;
        }

        // Fall back to IP address
        return 'ip:' . $request->ip();
    }
}
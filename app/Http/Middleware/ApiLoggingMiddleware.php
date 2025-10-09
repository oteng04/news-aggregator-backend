<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Logging\ApiLogger;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    private ApiLogger $apiLogger;

    public function __construct(ApiLogger $apiLogger)
    {
        $this->apiLogger = $apiLogger;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log the incoming request
        if ($request->is('api/*')) {
            $this->apiLogger->logRequest($request);
        }

        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        $duration = microtime(true) - $startTime;

        // Log the response
        if ($request->is('api/*') && $response instanceof JsonResponse) {
            $this->apiLogger->logResponse($request, $response);

            // Log performance metrics
            $this->apiLogger->logPerformance($request, $duration * 1000, [
                'status_code' => $response->getStatusCode(),
                'response_size' => strlen($response->getContent()),
            ]);
        }

        return $response;
    }

    /**
     * Handle exceptions and log them
     */
    public function handleException(Request $request, \Throwable $exception): void
    {
        if ($request->is('api/*')) {
            $this->apiLogger->logError($request, $exception, [
                'middleware' => 'ApiLoggingMiddleware',
                'uri' => $request->getUri(),
            ]);
        }
    }
}

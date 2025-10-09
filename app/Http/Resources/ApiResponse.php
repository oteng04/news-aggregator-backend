<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Create a successful response
     */
    public static function success(
        mixed $data = null,
        string $message = null,
        array $meta = [],
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version', '1.0.0'),
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a paginated response
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceKey = 'data',
        array $meta = []
    ): JsonResponse {
        $paginationMeta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];

        $response = [
            'success' => true,
            $resourceKey => $paginator->items(),
            'pagination' => $paginationMeta,
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version', '1.0.0'),
        ];

        if (!empty($meta)) {
            $response['meta'] = array_merge($response['meta'] ?? [], $meta);
        }

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Create an error response
     */
    public static function error(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        string $errorCode = null,
        array $details = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version', '1.0.0'),
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($details)) {
            $response['details'] = $details;
        }

        // Add debug information in development
        if (config('app.debug') && !empty($details['debug'])) {
            $response['debug'] = $details['debug'];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error(
            $message,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'VALIDATION_FAILED',
            ['errors' => $errors]
        );
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $resource = 'Resource', string $identifier = null): JsonResponse
    {
        $message = $identifier
            ? "{$resource} with identifier '{$identifier}' not found"
            : "{$resource} not found";

        return self::error(
            $message,
            Response::HTTP_NOT_FOUND,
            'RESOURCE_NOT_FOUND'
        );
    }

    /**
     * Create a rate limit exceeded response
     */
    public static function rateLimitExceeded(int $retryAfter = 60): JsonResponse
    {
        return self::error(
            'Too many requests. Please try again later.',
            Response::HTTP_TOO_MANY_REQUESTS,
            'RATE_LIMIT_EXCEEDED',
            ['retry_after' => $retryAfter]
        )->header('Retry-After', $retryAfter);
    }

    /**
     * Create a service unavailable response
     */
    public static function serviceUnavailable(string $service = null, array $context = []): JsonResponse
    {
        $message = $service
            ? "The {$service} service is currently unavailable"
            : "Service temporarily unavailable";

        return self::error(
            $message,
            Response::HTTP_SERVICE_UNAVAILABLE,
            'SERVICE_UNAVAILABLE',
            $context
        );
    }

    /**
     * Create a created response (for POST endpoints)
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, [], Response::HTTP_CREATED);
    }

    /**
     * Create an accepted response (for async operations)
     */
    public static function accepted(mixed $data = null, string $message = 'Request accepted for processing'): JsonResponse
    {
        return self::success($data, $message, [], Response::HTTP_ACCEPTED);
    }

    /**
     * Create a no content response (for DELETE operations)
     */
    public static function noContent(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version', '1.0.0'),
        ], Response::HTTP_NO_CONTENT);
    }
}

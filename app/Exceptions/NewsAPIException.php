<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsApiException extends Exception
{
    protected int $statusCode;
    protected array $context;
    protected string $errorCode;

    public const ERROR_CODES = [
        'API_UNAVAILABLE' => 'External API is currently unavailable',
        'RATE_LIMIT_EXCEEDED' => 'API rate limit exceeded',
        'INVALID_API_KEY' => 'Invalid or missing API key',
        'INVALID_RESPONSE' => 'Invalid response from external API',
        'NETWORK_ERROR' => 'Network connection failed',
        'TIMEOUT_ERROR' => 'Request timed out',
        'CONFIGURATION_ERROR' => 'Service configuration error',
    ];

    public function __construct(
        string $message = 'News API error',
        int $statusCode = 500,
        string $errorCode = 'API_ERROR',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function apiUnavailable(string $service, array $context = []): self
    {
        return new self(
            "The {$service} API is currently unavailable. Please try again later.",
            Response::HTTP_SERVICE_UNAVAILABLE,
            'API_UNAVAILABLE',
            array_merge($context, ['service' => $service])
        );
    }

    public static function rateLimitExceeded(string $service, int $retryAfter = 60): self
    {
        return new self(
            "API rate limit exceeded for {$service}. Please try again later.",
            Response::HTTP_TOO_MANY_REQUESTS,
            'RATE_LIMIT_EXCEEDED',
            ['service' => $service, 'retry_after' => $retryAfter]
        );
    }

    public static function invalidApiKey(string $service): self
    {
        return new self(
            "Invalid API key for {$service}. Please check your configuration.",
            Response::HTTP_UNAUTHORIZED,
            'INVALID_API_KEY',
            ['service' => $service]
        );
    }

    public static function networkError(string $service, string $details = ''): self
    {
        return new self(
            "Network error while connecting to {$service}: {$details}",
            Response::HTTP_BAD_GATEWAY,
            'NETWORK_ERROR',
            ['service' => $service, 'details' => $details]
        );
    }

    public static function timeoutError(string $service, int $timeout): self
    {
        return new self(
            "Request to {$service} timed out after {$timeout} seconds.",
            Response::HTTP_GATEWAY_TIMEOUT,
            'TIMEOUT_ERROR',
            ['service' => $service, 'timeout' => $timeout]
        );
    }

    public static function configurationError(string $service, string $details): self
    {
        return new self(
            "Configuration error for {$service}: {$details}",
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'CONFIGURATION_ERROR',
            ['service' => $service, 'details' => $details]
        );
    }

    public function render(Request $request): JsonResponse
    {
        $error = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'timestamp' => now()->toISOString(),
        ];

        // Add context in development/debug mode only
        if (config('app.debug')) {
            $error['debug'] = [
                'context' => $this->context,
                'file' => $this->getFile(),
                'line' => $this->getLine(),
            ];
        }

        // Add retry-after header for rate limiting
        if ($this->errorCode === 'RATE_LIMIT_EXCEEDED' && isset($this->context['retry_after'])) {
            return response()->json($error, $this->statusCode, [
                'Retry-After' => $this->context['retry_after']
            ]);
        }

        return response()->json($error, $this->statusCode);
    }
}
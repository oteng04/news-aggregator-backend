<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ApiExceptionHandler extends ExceptionHandler
{
    protected $dontReport = [
        // Add exceptions that shouldn't be reported to logs
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, \Throwable $exception): Response
    {
        // Handle API requests with custom error responses
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->renderApiResponse($request, $exception);
        }

        return parent::render($request, $exception);
    }

    protected function renderApiResponse(Request $request, \Throwable $exception): Response
    {
        // Handle custom NewsApiException
        if ($exception instanceof NewsApiException) {
            return $exception->render($request);
        }

        // Handle specific exception types
        return match (true) {
            $exception instanceof ValidationException => $this->handleValidationException($exception),
            $exception instanceof AuthenticationException => $this->handleAuthenticationException(),
            $exception instanceof AuthorizationException => $this->handleAuthorizationException(),
            $exception instanceof NotFoundHttpException => $this->handleNotFoundException(),
            $exception instanceof MethodNotAllowedHttpException => $this->handleMethodNotAllowedException(),
            $exception instanceof TooManyRequestsHttpException => $this->handleRateLimitException(),
            default => $this->handleGenericException($exception)
        };
    }

    protected function handleValidationException(ValidationException $exception): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $exception->errors(),
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function handleAuthenticationException(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required',
            'error_code' => 'AUTHENTICATION_REQUIRED',
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    protected function handleAuthorizationException(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient permissions',
            'error_code' => 'INSUFFICIENT_PERMISSIONS',
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_FORBIDDEN);
    }

    protected function handleNotFoundException(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Resource not found',
            'error_code' => 'RESOURCE_NOT_FOUND',
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_NOT_FOUND);
    }

    protected function handleMethodNotAllowedException(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Method not allowed',
            'error_code' => 'METHOD_NOT_ALLOWED',
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    protected function handleRateLimitException(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_TOO_MANY_REQUESTS, [
            'Retry-After' => 60
        ]);
    }

    protected function handleGenericException(\Throwable $exception): Response
    {
        $statusCode = method_exists($exception, 'getStatusCode')
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $response = [
            'success' => false,
            'message' => config('app.debug') ? $exception->getMessage() : 'An unexpected error occurred',
            'error_code' => 'INTERNAL_ERROR',
            'timestamp' => now()->toISOString(),
        ];

        // Add debug information in development
        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    public function report(\Throwable $exception): void
    {
        // Custom reporting logic can be added here
        // For example, send to error monitoring services

        parent::report($exception);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentNegotiationMiddleware
{
    /**
     * Supported content types
     */
    private const SUPPORTED_TYPES = [
        'application/json',
        'application/vnd.api+json', // JSON API
        'application/xml',          // Future XML support
        'text/xml',                 // Future XML support
    ];

    /**
     * Default content type
     */
    private const DEFAULT_CONTENT_TYPE = 'application/json';

    public function handle(Request $request, Closure $next): Response
    {
        // Check Accept header
        $acceptHeader = $request->header('Accept', self::DEFAULT_CONTENT_TYPE);

        // Determine response content type
        $contentType = $this->negotiateContentType($acceptHeader);

        // Store negotiated content type for use in controllers
        $request->merge(['_content_type' => $contentType]);

        $response = $next($request);

        // Set Content-Type header on response
        if ($response instanceof Response) {
            $response->headers->set('Content-Type', $contentType . '; charset=utf-8');

            // Add API versioning headers
            $response->headers->set('X-API-Version', config('app.api_version', '1.0.0'));

            // Add request ID for tracing
            if (!$response->headers->has('X-Request-ID')) {
                $response->headers->set('X-Request-ID', uniqid('req_', true));
            }

            // Add security headers for API responses
            $this->addSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Negotiate the best content type based on Accept header
     */
    private function negotiateContentType(string $acceptHeader): string
    {
        // Parse Accept header (simple implementation)
        $acceptedTypes = array_map('trim', explode(',', $acceptHeader));

        // Remove quality parameters (q=0.8, etc.)
        $acceptedTypes = array_map(function ($type) {
            return explode(';', $type)[0];
        }, $acceptedTypes);

        // Find the first supported type
        foreach ($acceptedTypes as $type) {
            if ($type === '*/*' || in_array($type, self::SUPPORTED_TYPES)) {
                // For now, only support JSON
                if ($type === '*/*' || str_contains($type, 'json')) {
                    return 'application/json';
                }
            }
        }

        return self::DEFAULT_CONTENT_TYPE;
    }

    /**
     * Add security headers to API responses
     */
    private function addSecurityHeaders(Response $response): void
    {
        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict potentially harmful browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Cache control for API responses
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
    }
}

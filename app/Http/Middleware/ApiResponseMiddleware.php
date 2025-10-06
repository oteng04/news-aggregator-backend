<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/*')) {
            $data = $response->getData(true);
            
            if (!isset($data['success'])) {
                $response->setData([
                    'success' => true,
                    'data' => $data
                ]);
            }
        }

        return $response;
    }
}
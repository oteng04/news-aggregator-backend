<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionHandler extends ExceptionHandler
{
    public function render($request, \Throwable $exception): Response
    {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $exception->getMessage()
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
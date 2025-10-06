<?php

namespace App\Exceptions;

use Exception;

class ArticleNotFoundException extends Exception
{
    public function __construct(string $message = 'Article not found', int $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Article not found'
        ], 404);
    }
}
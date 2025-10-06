<?php

namespace App\Exceptions;

use Exception;

class NewsAPIException extends Exception
{
    public function __construct(string $message = 'News API error', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch news articles',
            'error' => $this->getMessage()
        ], 500);
    }
}
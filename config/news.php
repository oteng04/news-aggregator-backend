<?php

return [
    'providers' => [
        'news_api' => [
            'name' => 'News API',
            'base_url' => env('NEWS_API_BASE_URL', 'https://newsapi.org/v2'),
            'api_key' => env('NEWS_API_KEY'),
            'enabled' => env('NEWS_API_ENABLED', true),
        ],
        'guardian' => [
            'name' => 'The Guardian',
            'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
            'api_key' => env('GUARDIAN_API_KEY'),
            'enabled' => env('GUARDIAN_ENABLED', true),
        ],
        'ny_times' => [
            'name' => 'New York Times',
            'base_url' => env('NYTIMES_BASE_URL', 'https://api.nytimes.com/svc'),
            'api_key' => env('NYT_API_KEY'),
            'enabled' => env('NYTIMES_ENABLED', true),
        ],
    ],
    'defaults' => [
        'per_page' => 20,
        'max_per_page' => 100,
        'timeout' => 30,
    ],
];
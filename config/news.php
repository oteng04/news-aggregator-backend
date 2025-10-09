<?php

return [
    'providers' => [
        'news_api' => [
            'name' => 'News API',
            'api_key' => env('NEWSAPI_API_KEY'),
            'base_url' => env('NEWSAPI_BASE_URL', 'https://newsapi.org/v2'),
            'endpoints' => [
                'top_headlines' => 'top-headlines',
                'everything' => 'everything',
            ],
            'default_params' => [
                'country' => 'us',
                'pageSize' => 50,
                'sortBy' => 'publishedAt',
                'language' => 'en',
            ],
        ],
        'guardian' => [
            'name' => 'The Guardian',
            'api_key' => env('GUARDIAN_API_KEY'),
            'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
            'endpoints' => [
                'search' => 'search',
            ],
            'default_params' => [
                'page-size' => 50,
                'order-by' => 'newest',
                'show-fields' => 'headline,trailText,body,thumbnail,byline',
            ],
        ],
        'ny_times' => [
            'name' => 'New York Times',
            'api_key' => env('NYT_API_KEY'),
            'base_url' => env('NYTIMES_BASE_URL', 'https://api.nytimes.com/svc'),
            'endpoints' => [
                'top_stories' => 'topstories/v2/home.json',
                'search' => 'search/v2/articlesearch.json',
            ],
            'default_params' => [
                'sort' => 'newest',
            ],
        ],
    ],

    'cache' => [
        'ttl' => env('NEWS_CACHE_TTL', 1800), // 30 minutes
        'prefix' => 'news_api_',
    ],

    'rate_limiting' => [
        'enabled' => env('NEWS_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('NEWS_RATE_LIMIT_REQUESTS', 60),
    ],
];
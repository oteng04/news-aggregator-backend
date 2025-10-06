<?php

namespace App\Enums;

enum NewsProvider: string
{
    case NEWS_API = 'news_api';
    case GUARDIAN = 'guardian';
    case NY_TIMES = 'ny_times';

    public function getDisplayName(): string
    {
        return match($this) {
            self::NEWS_API => 'News API',
            self::GUARDIAN => 'The Guardian',
            self::NY_TIMES => 'New York Times',
        };
    }

    public static function getAll(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
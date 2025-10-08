<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface NewsSourceInterface
{
    public function fetchArticles(string $category = 'general', int $page = 1): Collection;

    public function searchArticles(string $query, int $page = 1): Collection;

    public function getSourceName(): string;

    public function getApiIdentifier(): string;
}
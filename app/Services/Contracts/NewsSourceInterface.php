<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface NewsSourceInterface
{
    public function fetchArticles(int $page = 1, array $options = []): Collection;

    public function searchArticles(string $query, int $page = 1, array $options = []): Collection;

    public function getSourceName(): string;

    public function getApiIdentifier(): string;
}
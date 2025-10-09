<?php

namespace App\Services;

use App\Services\Normalizers\NewsApiNormalizer;

class NewsAPIService extends BaseNewsService
{
    public function __construct()
    {
        parent::__construct('news_api');
    }

    protected function createNormalizer()
    {
        return new NewsApiNormalizer();
    }

    protected function getFetchEndpoint(): string
    {
        return $this->config['endpoints']['top_headlines'];
    }

    protected function getSearchEndpoint(): string
    {
        return $this->config['endpoints']['everything'];
    }

    protected function getFetchParams(int $page, array $options): array
    {
        return array_merge($this->config['default_params'], [
            'page' => $page,
        ], $options);
    }

    protected function getSearchParams(string $query, int $page, array $options): array
    {
        return array_merge($this->config['default_params'], [
            'q' => $query,
            'page' => $page,
        ], $options);
    }
}
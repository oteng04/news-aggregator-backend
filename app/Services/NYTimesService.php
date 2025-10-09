<?php

namespace App\Services;

use App\Services\Normalizers\NYTimesNormalizer;

class NYTimesService extends BaseNewsService
{
    public function __construct()
    {
        parent::__construct('ny_times');
    }

    protected function createNormalizer()
    {
        return new NYTimesNormalizer();
    }

    protected function getFetchEndpoint(): string
    {
        return $this->config['endpoints']['top_stories'];
    }

    protected function getSearchEndpoint(): string
    {
        return $this->config['endpoints']['search'];
    }

    protected function getFetchParams(int $page, array $options): array
    {
        return array_merge($this->config['default_params'], $options);
    }

    protected function getSearchParams(string $query, int $page, array $options): array
    {
        return array_merge($this->config['default_params'], [
            'q' => $query,
            'page' => $page,
        ], $options);
    }
}
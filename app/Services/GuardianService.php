<?php

namespace App\Services;

use App\Services\Normalizers\GuardianNormalizer;

class GuardianService extends BaseNewsService
{
    public function __construct()
    {
        parent::__construct('guardian');
    }

    protected function createNormalizer()
    {
        return new GuardianNormalizer();
    }

    protected function getFetchEndpoint(): string
    {
        return $this->config['endpoints']['search'];
    }

    protected function getSearchEndpoint(): string
    {
        return $this->config['endpoints']['search'];
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
<?php

namespace App\Providers;

use App\Repositories\ArticleRepository;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
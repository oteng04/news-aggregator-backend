<?php

namespace App\Providers;

use App\Services\NewsAPIService;
use App\Services\GuardianService;
use App\Services\NYTimesService;
use App\Services\ArticleAggregatorService;
use App\Services\Contracts\NewsSourceInterface;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NewsAPIService::class);
        $this->app->singleton(GuardianService::class);
        $this->app->singleton(NYTimesService::class);
        $this->app->singleton(ArticleAggregatorService::class);
    }

    public function boot(): void
    {
        //
    }
}
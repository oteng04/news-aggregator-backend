<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Article;
use App\Models\Source;
use App\Models\Category;
use App\Models\Author;
use App\Observers\ArticleObserver;
use App\Observers\SourceObserver;
use App\Observers\CategoryObserver;
use App\Observers\AuthorObserver;
use App\Services\Cache\CacheService;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Logging\ApiLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Cache Service as singleton
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });

        // Register System Monitor
        $this->app->singleton(SystemMonitor::class, function ($app) {
            return new SystemMonitor($app->make(CacheService::class));
        });

        // Register API Logger
        $this->app->singleton(ApiLogger::class, function ($app) {
            return new ApiLogger();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for automatic cache invalidation
        Article::observe(ArticleObserver::class);
        Source::observe(SourceObserver::class);
        Category::observe(CategoryObserver::class);
        Author::observe(AuthorObserver::class);

        // Warm up caches on application boot (only in production)
        if ($this->app->environment('production')) {
            $this->warmUpCaches();
        }
    }

    /**
     * Warm up frequently accessed caches
     */
    private function warmUpCaches(): void
    {
        try {
            $cacheService = app(CacheService::class);

            // Dispatch cache warming to background to avoid blocking app startup
            dispatch(function () use ($cacheService) {
                $cacheService->warmUpCaches();
            })->delay(now()->addSeconds(30)); // Delay by 30 seconds after app start

        } catch (\Exception $e) {
            // Log error but don't fail app startup
            \Illuminate\Support\Facades\Log::warning('Failed to warm up caches', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

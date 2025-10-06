<?php

namespace App\Jobs;

use App\Services\ArticleAggregatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private string $category = 'general'
    ) {}

    public function handle(ArticleAggregatorService $aggregatorService): void
    {
        try {
            Log::info("Starting article fetch job for category: {$this->category}");
            
            $totalFetched = $aggregatorService->fetchAllArticles($this->category);
            
            Log::info("Article fetch job completed", [
                'category' => $this->category,
                'total_fetched' => $totalFetched
            ]);
        } catch (\Exception $e) {
            Log::error("Article fetch job failed", [
                'category' => $this->category,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Article fetch job failed permanently", [
            'category' => $this->category,
            'error' => $exception->getMessage()
        ]);
    }
}
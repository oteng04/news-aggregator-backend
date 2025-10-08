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

    

    public function handle(ArticleAggregatorService $aggregatorService): void
    {
        try {
            Log::info("Starting article fetch job");
            
            $totalFetched = $aggregatorService->fetchAllArticles();
            
            Log::info("Article fetch job completed", [
                'total_fetched' => $totalFetched
            ]);
        } catch (\Exception $e) {
            Log::error("Article fetch job failed", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Article fetch job failed permanently", [
            'error' => $exception->getMessage()
        ]);
    }
}
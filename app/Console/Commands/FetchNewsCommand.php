<?php

namespace App\Console\Commands;

use App\Jobs\FetchArticlesJob;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Fetch news articles from all sources';

    public function handle(): int
    {
        
        $this->info("Dispatching job to fetch  news...");
        
        FetchArticlesJob::dispatch();
        
        $this->info('Job dispatched successfully!');
        
        return self::SUCCESS;
    }
}
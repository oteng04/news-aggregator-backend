<?php

namespace App\Console\Commands;

use App\Jobs\FetchArticlesJob;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch {category=general}';
    protected $description = 'Fetch news articles from all sources';

    public function handle(): int
    {
        $category = $this->argument('category');
        
        $this->info("Dispatching job to fetch {$category} news...");
        
        FetchArticlesJob::dispatch($category);
        
        $this->info('Job dispatched successfully!');
        
        return self::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cache\CacheService;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage
                            {action : Action to perform (warmup|stats|clear|invalidate)}
                            {--tags=* : Cache tags to invalidate (for invalidate action)}
                            {--table= : Database table to invalidate (for invalidate action)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application caches with warmup, stats, and invalidation';

    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'warmup':
                return $this->warmUpCaches();
            case 'stats':
                return $this->showCacheStats();
            case 'clear':
                return $this->clearAllCaches();
            case 'invalidate':
                return $this->invalidateCaches();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: warmup, stats, clear, invalidate');
                return 1;
        }
    }

    private function warmUpCaches(): int
    {
        $this->info('🔥 Warming up application caches...');

        $startTime = microtime(true);
        $results = $this->cacheService->warmUpCaches();
        $duration = round(microtime(true) - $startTime, 2);

        $this->info("✅ Cache warmup completed in {$duration}s");

        $this->table(
            ['Cache Type', 'Status'],
            [
                ['Statistics', $this->formatWarmupResult($results['stats'] ?? [])],
                ['API Responses', $this->formatWarmupResult($results['api'] ?? [])],
                ['Models', $this->formatWarmupResult($results['models'] ?? [])],
            ]
        );

        return 0;
    }

    private function showCacheStats(): int
    {
        $this->info('📊 Cache Statistics');

        $stats = $this->cacheService->getCacheStats();

        if (isset($stats['error'])) {
            $this->error($stats['error']);
            return 1;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Hits', number_format($stats['hits'])],
                ['Cache Misses', number_format($stats['misses'])],
                ['Hit Rate', $stats['hit_rate'] . '%'],
                ['Total Keys', number_format($stats['total_keys'])],
                ['Memory Used', $stats['memory_used']],
                ['Uptime', $stats['uptime_days'] . ' days'],
            ]
        );

        // Show cache tag information
        $this->newLine();
        $this->info('🏷️  Cache Tags:');
        $tags = ['articles', 'sources', 'categories', 'authors', 'api_responses', 'stats'];

        foreach ($tags as $tag) {
            $count = \Illuminate\Support\Facades\Cache::tags([$tag])->getStore()->getPrefix() ?
                'Active' : 'Empty';
            $this->line("  {$tag}: {$count}");
        }

        return 0;
    }

    private function clearAllCaches(): int
    {
        if (!$this->confirm('Are you sure you want to clear ALL caches? This will impact performance temporarily.')) {
            $this->info('Cache clearing cancelled.');
            return 0;
        }

        $this->info('🧹 Clearing all application caches...');

        $startTime = microtime(true);
        $results = $this->cacheService->clearAllCaches();
        $duration = round(microtime(true) - $startTime, 2);

        $this->info("✅ All caches cleared in {$duration}s");

        $this->table(
            ['Cache Type', 'Status'],
            array_map(fn($type, $status) => [$type, $status], array_keys($results), $results)
        );

        return 0;
    }

    private function invalidateCaches(): int
    {
        $tags = $this->option('tags');
        $table = $this->option('table');

        if (empty($tags) && empty($table)) {
            $this->error('Please specify either --tags or --table option');
            return 1;
        }

        if (!empty($tags)) {
            $this->info("🗑️  Invalidating cache tags: " . implode(', ', $tags));
            $this->cacheService->invalidateTags($tags);
            $this->info('✅ Cache tags invalidated');
        }

        if (!empty($table)) {
            $this->info("🗑️  Invalidating table cache: {$table}");
            $this->cacheService->invalidateTableCache($table);
            $this->info('✅ Table cache invalidated');
        }

        return 0;
    }

    private function formatWarmupResult(array $result): string
    {
        if (is_numeric($result)) {
            return "✅ {$result} items cached";
        }

        $successCount = count(array_filter($result, fn($item) => !str_starts_with($item, 'error')));
        $totalCount = count($result);

        if ($successCount === $totalCount) {
            return "✅ All {$totalCount} items cached";
        } else {
            return "⚠️  {$successCount}/{$totalCount} items cached";
        }
    }
}

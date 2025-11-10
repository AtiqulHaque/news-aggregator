<?php

namespace App\Console\Commands;

use App\Jobs\CrawlCampaignJob;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\NewsSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleCrawlJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:schedule-crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule crawl jobs for all active campaigns and their sources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to schedule crawl jobs for active campaigns...');

        Log::info('Starting crawl job scheduler');

        // Get all active campaigns with status 'running'
        $activeCampaigns = Campaign::where('status', 'running')
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with('sources')
            ->get();

        if ($activeCampaigns->isEmpty()) {
            $this->warn('No active campaigns found.');
            Log::info('No active campaigns found for crawl scheduling');
            return Command::SUCCESS;
        }

        $this->info("Found {$activeCampaigns->count()} active campaign(s).");

        Log::info('Found active campaigns for crawl scheduling', [
            'campaigns_count' => $activeCampaigns->count(),
        ]);

        $totalJobsDispatched = 0;

        foreach ($activeCampaigns as $campaign) {
            // Get active sources for this campaign
            // Since we eager loaded 'sources', we can filter the collection directly
            // This is more reliable than querying the relationship again
            try {
                // Filter the eager-loaded sources collection for active sources
                $sources = $campaign->sources->where('is_active', true);

                Log::info('Found sources for campaign', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'total_sources' => $campaign->sources->count(),
                    'active_sources' => $sources->count(),
                ]);

                if ($sources->isEmpty()) {

                    $this->warn("Campaign '{$campaign->name}' has no active sources.");
                    Log::info('No active sources found for campaign', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                    ]);
                    continue;
                }

                $this->info("Processing campaign '{$campaign->name}' with {$sources->count()} source(s)...");

                foreach ($sources as $source) {
                    Log::info('Processing source', ['source' => $source->toArray()]);
                    // Check if it's time to crawl based on crawl_interval_minutes
                    $shouldCrawl = $this->shouldCrawl($source, $campaign);

                    if (!$shouldCrawl) {
                        Log::info('Skipping source (not yet time to crawl)', [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'source_id' => $source->id,
                            'source_name' => $source->name,
                        ]);
                        $this->line("  - Skipping source '{$source->name}' (not yet time to crawl)");
                        continue;
                    }

                    // Delete all existing articles for this source before crawling
                    $deletedCount = Article::where('source_id', $source->id)->delete();

                    Log::info('Deleted existing articles for source before crawl', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'source_id' => $source->id,
                        'source_name' => $source->name,
                        'deleted_count' => $deletedCount,
                    ]);

                    if ($deletedCount > 0) {
                        $this->line("  - Deleted {$deletedCount} existing article(s) for source '{$source->name}'");
                    }

                    // Dispatch crawl job
                    CrawlCampaignJob::dispatch($campaign->id, $source->id)
                        ->onQueue('crawling');

                    $totalJobsDispatched++;
                    $this->line("  - Dispatched crawl job for source '{$source->name}'");

                    Log::info('Crawl job dispatched', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'source_id' => $source->id,
                        'source_name' => $source->name,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Error finding sources for campaign', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        $this->info("Successfully dispatched {$totalJobsDispatched} crawl job(s).");

        Log::info('Crawl job scheduling completed', [
            'total_jobs_dispatched' => $totalJobsDispatched,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Determine if a source should be crawled based on crawl interval.
     */
    private function shouldCrawl(NewsSource $source, Campaign $campaign): bool
    {
        // If never crawled, should crawl
        if (!$source->last_crawled_at) {
            return true;
        }

        // Check if crawl interval has passed (use copy() to avoid modifying the original)
        $nextCrawlTime = $source->last_crawled_at->copy()->addMinutes($source->crawl_interval_minutes);

        return now()->greaterThanOrEqualTo($nextCrawlTime);
    }
}

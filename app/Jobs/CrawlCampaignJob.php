<?php

namespace App\Jobs;

use App\Jobs\IndexArticleToElasticsearch;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\CrawlJob;
use App\Models\NewsSource;
use App\Services\Crawlers\CrawlerServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $campaignId,
        public int $sourceId
    ) {
        //
    }

    /**
     * Get the crawler service manager.
     */
    private function getCrawlerManager(): CrawlerServiceManager
    {
        return app(CrawlerServiceManager::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $jobId = uniqid('crawl_', true);

        try {
            // Log at the very start to ensure we can see if the job is being executed
            Log::channel('worker')->info('CrawlCampaignJob: Starting execution', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'queue' => $this->queue ?? 'default',
                'attempts' => $this->attempts(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Also log to default channel for visibility
            Log::info('CrawlCampaignJob: Starting execution', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
            ]);
        } catch (\Exception $e) {
            // If logging fails, at least we know something is wrong
            error_log('CrawlCampaignJob: Failed to log start - ' . $e->getMessage());
        }


        try {
            Log::channel('worker')->info('CrawlCampaignJob: Loading models', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
            ]);

            $campaign = Campaign::findOrFail($this->campaignId);
            $source = NewsSource::findOrFail($this->sourceId);

            Log::channel('worker')->info('CrawlCampaignJob: Models loaded successfully', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'campaign_name' => $campaign->name,
                'source_name' => $source->name,
            ]);

            Log::info('Starting crawl job', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'campaign_name' => $campaign->name,
                'source_name' => $source->name,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('worker')->error('CrawlCampaignJob: Model not found', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'error' => $e->getMessage(),
            ]);
            Log::error('CrawlCampaignJob: Model not found', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::channel('worker')->error('CrawlCampaignJob: Error loading models', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Log::error('CrawlCampaignJob: Error loading models', [
                'job_id' => $jobId,
                'campaign_id' => $this->campaignId,
                'source_id' => $this->sourceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Create crawl job record
        Log::channel('worker')->info('CrawlCampaignJob: Creating crawl job record', [
            'job_id' => $jobId,
            'campaign_id' => $this->campaignId,
            'source_id' => $this->sourceId,
        ]);

        $crawlJob = CrawlJob::create([
            'campaign_id' => $this->campaignId,
            'source_id' => $this->sourceId,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Log::channel('worker')->info('CrawlCampaignJob: Crawl job record created', [
            'job_id' => $jobId,
            'crawl_job_id' => $crawlJob->id,
        ]);

        try {
            // Get the appropriate crawler for this source
            Log::channel('worker')->info('CrawlCampaignJob: Getting crawler manager', [
                'job_id' => $jobId,
                'source_id' => $source->id,
            ]);

            $crawlerManager = $this->getCrawlerManager();
            $crawler = $crawlerManager->getCrawlerForSource($source);

            Log::channel('worker')->info('CrawlCampaignJob: Using crawler for source', [
                'job_id' => $jobId,
                'source_id' => $source->id,
                'source_name' => $source->name,
                'crawler_name' => $crawler->getName(),
            ]);

            Log::info('Using crawler for source', [
                'job_id' => $jobId,
                'source_id' => $source->id,
                'source_name' => $source->name,
                'crawler_name' => $crawler->getName(),
            ]);

            Log::channel('worker')->info('CrawlCampaignJob: Starting crawl operation', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'source_id' => $source->id,
            ]);

            $articles = $crawler->crawl($source);

            Log::channel('worker')->info('CrawlCampaignJob: Crawl operation completed', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'articles_found' => count($articles),
            ]);

            // Save articles to database
            $savedArticles = [];
            Log::channel('worker')->info('CrawlCampaignJob: Starting to save articles', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'total_articles' => count($articles),
            ]);

            foreach ($articles as $index => $articleData) {
                $article = Article::create([
                    'campaign_id' => $this->campaignId,
                    'source_id' => $this->sourceId,
                    'crawl_job_id' => $crawlJob->id,
                    'title' => $articleData['title'],
                    'content' => $articleData['content'] ?? null,
                    'url' => $articleData['url'],
                    'author' => $articleData['author'] ?? null,
                    'published_at' => $articleData['published_at'] ?? null,
                    'summary' => $articleData['summary'] ?? null,
                    'metadata' => $articleData['metadata'] ?? [],
                ]);

                $savedArticles[] = $article;

                // Dispatch job to index article to Elasticsearch
                IndexArticleToElasticsearch::dispatch($article->id)
                    ->onQueue('elasticsearch');

                if (($index + 1) % 10 == 0) {
                    Log::channel('worker')->info('CrawlCampaignJob: Progress saving articles', [
                        'job_id' => $jobId,
                        'crawl_job_id' => $crawlJob->id,
                        'saved_count' => count($savedArticles),
                        'total_count' => count($articles),
                    ]);
                }
            }

            Log::channel('worker')->info('CrawlCampaignJob: All articles saved', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'total_saved' => count($savedArticles),
            ]);

            // Update crawl job as successful
            $crawlJob->update([
                'finished_at' => now(),
                'status' => 'success',
                'total_articles' => count($savedArticles),
            ]);

            // Update source's last_crawled_at
            $source->update(['last_crawled_at' => now()]);

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::channel('worker')->info('CrawlCampaignJob: Job completed successfully', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'articles_count' => count($savedArticles),
                'execution_time_seconds' => $executionTime,
            ]);

            Log::info('Crawl job completed successfully', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'articles_count' => count($savedArticles),
                'execution_time_seconds' => $executionTime,
            ]);
        } catch (\Exception $e) {
            // Update crawl job as failed
            $crawlJob->update([
                'finished_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::channel('worker')->error('CrawlCampaignJob: Job failed', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'execution_time_seconds' => $executionTime,
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error('Crawl job failed', [
                'job_id' => $jobId,
                'crawl_job_id' => $crawlJob->id,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'execution_time_seconds' => $executionTime,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

}

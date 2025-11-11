<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\CampaignSource;
use App\Models\CrawlJob;
use App\Models\NewsSource;
use App\Models\User;
use App\Services\ElasticsearchService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(
        private ElasticsearchService $elasticsearchService
    ) {
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Cleaning up old data...');

        // Delete in order to respect foreign key constraints
        // 1. Delete articles (depends on campaigns and sources)
        $articlesDeleted = Article::query()->delete();
        $this->command->info("  - Deleted {$articlesDeleted} article(s) from database");

        // 2. Delete crawl jobs (depends on campaigns and sources)
        $crawlJobsDeleted = CrawlJob::query()->delete();
        $this->command->info("  - Deleted {$crawlJobsDeleted} crawl job(s) from database");

        // 3. Delete campaign-source associations (pivot table)
        $campaignSourcesDeleted = CampaignSource::query()->delete();
        $this->command->info("  - Deleted {$campaignSourcesDeleted} campaign-source association(s) from database");

        // 4. Delete campaigns
        $campaignsDeleted = Campaign::query()->delete();
        $this->command->info("  - Deleted {$campaignsDeleted} campaign(s) from database");

        // 5. Delete news sources
        $sourcesDeleted = NewsSource::query()->delete();
        $this->command->info("  - Deleted {$sourcesDeleted} news source(s) from database");

        // Delete all articles from Elasticsearch
        try {
            $esQuery = [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ];

            $elasticsearchHost = config('services.elasticsearch.host', env('ELASTICSEARCH_HOST', 'elasticsearch'));
            $elasticsearchPort = config('services.elasticsearch.port', env('ELASTICSEARCH_PORT', 9200));
            $index = 'articles';
            $url = "http://{$elasticsearchHost}:{$elasticsearchPort}/{$index}/_delete_by_query";

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, $esQuery);

            if ($response->successful()) {
                $result = $response->json();
                $esDeleted = $result['deleted'] ?? 0;
                $this->command->info("  - Deleted {$esDeleted} article(s) from Elasticsearch");
            } else {
                $this->command->warn("  - Failed to delete articles from Elasticsearch: {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->command->warn("  - Error deleting articles from Elasticsearch: {$e->getMessage()}");
            Log::error('Error deleting from Elasticsearch during seeder', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->command->info('Old data cleanup completed.');
        $this->command->newLine();

        // User::factory(10)->create();

        // Create test user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]
        );

        $this->call([
            CampaignSeeder::class,
            NewsSourceSeeder::class,
            CampaignSourceSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}

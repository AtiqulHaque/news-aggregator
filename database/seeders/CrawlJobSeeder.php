<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CrawlJob;
use App\Models\NewsSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrawlJobSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campaigns = Campaign::all();
        $sources = NewsSource::all();

        if ($campaigns->isEmpty() || $sources->isEmpty()) {
            $this->command->warn('No campaigns or sources found. Please run CampaignSeeder and NewsSourceSeeder first.');
            return;
        }

        $now = now();

        // Create pending jobs
        for ($i = 0; $i < 5; $i++) {
            CrawlJob::create([
                'campaign_id' => $campaigns->random()->id,
                'source_id' => $sources->random()->id,
                'started_at' => $now->copy()->addHours($i),
                'status' => 'pending',
                'total_articles' => 0,
            ]);
        }

        // Create in_progress jobs
        for ($i = 0; $i < 3; $i++) {
            CrawlJob::create([
                'campaign_id' => $campaigns->random()->id,
                'source_id' => $sources->random()->id,
                'started_at' => $now->copy()->subMinutes(30 + $i * 10),
                'status' => 'in_progress',
                'total_articles' => rand(10, 50),
            ]);
        }

        // Create successful jobs
        for ($i = 0; $i < 8; $i++) {
            $started = $now->copy()->subHours(rand(1, 48));
            CrawlJob::create([
                'campaign_id' => $campaigns->random()->id,
                'source_id' => $sources->random()->id,
                'started_at' => $started,
                'finished_at' => $started->copy()->addMinutes(rand(5, 30)),
                'status' => 'success',
                'total_articles' => rand(20, 100),
            ]);
        }

        // Create failed jobs
        for ($i = 0; $i < 2; $i++) {
            $started = $now->copy()->subHours(rand(1, 24));
            CrawlJob::create([
                'campaign_id' => $campaigns->random()->id,
                'source_id' => $sources->random()->id,
                'started_at' => $started,
                'finished_at' => $started->copy()->addMinutes(rand(1, 5)),
                'status' => 'failed',
                'total_articles' => 0,
                'error_message' => fake()->randomElement([
                    'Connection timeout',
                    'Invalid response format',
                    'Rate limit exceeded',
                    'Source unavailable',
                ]),
            ]);
        }
    }
}

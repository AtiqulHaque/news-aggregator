<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CrawlJob;
use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrawlJob>
 */
class CrawlJobFactory extends Factory
{
    protected $model = CrawlJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-7 days', 'now');
        $status = fake()->randomElement(['pending', 'in_progress', 'success', 'failed']);
        $finishedAt = in_array($status, ['success', 'failed']) 
            ? fake()->dateTimeBetween($startedAt, 'now')
            : null;

        return [
            'campaign_id' => Campaign::factory(),
            'source_id' => NewsSource::factory(),
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'status' => $status,
            'total_articles' => $status === 'success' ? fake()->numberBetween(10, 200) : 0,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
        ];
    }
}

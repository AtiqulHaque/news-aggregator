<?php

namespace Database\Factories;

use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsSource>
 */
class NewsSourceFactory extends Factory
{
    protected $model = NewsSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' News',
            'base_url' => fake()->url(),
            'source_type' => fake()->randomElement(['website', 'rss', 'api']),
            'crawl_interval_minutes' => fake()->randomElement([30, 60, 90, 120, 180, 240]),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'last_crawled_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}

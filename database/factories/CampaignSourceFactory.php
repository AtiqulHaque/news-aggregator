<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSource;
use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignSource>
 */
class CampaignSourceFactory extends Factory
{
    protected $model = CampaignSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'source_id' => NewsSource::factory(),
        ];
    }
}

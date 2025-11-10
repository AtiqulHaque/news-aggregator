<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignSource;
use App\Models\NewsSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignSourceSeeder extends Seeder
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

        // Associate each campaign with 2-4 random sources
        foreach ($campaigns as $campaign) {
            foreach ($sources as $source) {
                // Check if association already exists
                $exists = CampaignSource::where('campaign_id', $campaign->id)
                    ->where('source_id', $source->id)
                    ->exists();

                if (!$exists) {
                    CampaignSource::create([
                        'campaign_id' => $campaign->id,
                        'source_id' => $source->id,
                    ]);
                }
            }
        }

        $this->command->info('Campaign-source associations created successfully.');
    }
}

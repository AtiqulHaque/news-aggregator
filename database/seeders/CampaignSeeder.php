<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create campaigns with different statuses
        $now = now();

        // Scheduled campaigns (future)
        Campaign::factory()->count(5)->create([
            'status' => 'scheduled',
            'start_date' => $now->copy()->addDays(7),
            'end_date' => $now->copy()->addDays(37),
        ]);

        // Running campaigns (active)
        Campaign::factory()->count(3)->create([
            'status' => 'running',
            'start_date' => $now->copy()->subDays(5),
            'end_date' => $now->copy()->addDays(25),
        ]);

        // Completed campaigns (past)
        Campaign::factory()->count(4)->create([
            'status' => 'completed',
            'start_date' => $now->copy()->subDays(60),
            'end_date' => $now->copy()->subDays(30),
        ]);

        // Failed campaigns
        Campaign::factory()->count(2)->create([
            'status' => 'failed',
            'start_date' => $now->copy()->subDays(30),
            'end_date' => $now->copy()->subDays(10),
        ]);

        // Create some campaigns with different frequencies
        Campaign::factory()->create([
            'name' => 'Daily Newsletter Campaign',
            'description' => 'Sends daily newsletter to all subscribers',
            'status' => 'running',
            'start_date' => $now->copy()->subDays(10),
            'end_date' => $now->copy()->addDays(20),
            'frequency_minutes' => 1440, // Daily
        ]);

        Campaign::factory()->create([
            'name' => 'Weekly Report Campaign',
            'description' => 'Weekly summary report for stakeholders',
            'status' => 'scheduled',
            'start_date' => $now->copy()->addDays(3),
            'end_date' => $now->copy()->addDays(90),
            'frequency_minutes' => 10080, // Weekly (7 days * 24 hours * 60 minutes)
        ]);

        Campaign::factory()->create([
            'name' => 'Hourly Monitoring Campaign',
            'description' => 'System health monitoring every hour',
            'status' => 'running',
            'start_date' => $now->copy()->subDays(1),
            'end_date' => $now->copy()->addDays(30),
            'frequency_minutes' => 60, // Hourly
        ]);
    }
}

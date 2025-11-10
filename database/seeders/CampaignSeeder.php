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
        // Create some campaigns with different frequencies
        Campaign::factory()->create([
            'name' => 'Daily CNN News Newsletter Campaign',
            'description' => 'Sends daily CNN News newsletter to all subscribers',
            'status' => 'running',
            'start_date' => $now->copy()->subDays(10),
            'end_date' => null,
            'frequency_minutes' => 1440, // Daily
        ]);

        // Campaign::factory()->create([
        //     'name' => 'Daily BBC News Newsletter Campaign',
        //     'description' => 'Sends daily BBC News newsletter to all subscribers',
        //     'status' => 'running',
        //     'start_date' => $now->copy()->subDays(10),
        //     'end_date' => null,
        //     'frequency_minutes' => 1440, // Daily
        // ]);
    }
}

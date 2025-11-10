<?php

namespace Database\Seeders;

use App\Models\NewsSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Website Sources
        NewsSource::create([
            'name' => 'CNN News',
            'base_url' => 'edition.cnn.com',
            'source_type' => 'website',
            'crawl_interval_minutes' => 5,
            'is_active' => true,
        ]);
    }
}

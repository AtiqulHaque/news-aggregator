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
        // RSS Sources
        NewsSource::create([
            'name' => 'BBC News',
            'base_url' => 'https://www.bbc.com',
            'source_type' => 'rss',
            'crawl_interval_minutes' => 60,
            'is_active' => true,
        ]);

        NewsSource::create([
            'name' => 'Reuters',
            'base_url' => 'https://www.reuters.com',
            'source_type' => 'rss',
            'crawl_interval_minutes' => 30,
            'is_active' => true,
        ]);

        NewsSource::create([
            'name' => 'CNN',
            'base_url' => 'https://www.cnn.com',
            'source_type' => 'rss',
            'crawl_interval_minutes' => 45,
            'is_active' => true,
        ]);

        // API Sources
        NewsSource::create([
            'name' => 'NewsAPI',
            'base_url' => 'https://newsapi.org',
            'source_type' => 'api',
            'crawl_interval_minutes' => 120,
            'is_active' => true,
        ]);

        NewsSource::create([
            'name' => 'Guardian API',
            'base_url' => 'https://content.guardianapis.com',
            'source_type' => 'api',
            'crawl_interval_minutes' => 90,
            'is_active' => true,
        ]);

        // Website Sources
        NewsSource::create([
            'name' => 'TechCrunch',
            'base_url' => 'https://techcrunch.com',
            'source_type' => 'website',
            'crawl_interval_minutes' => 180,
            'is_active' => true,
        ]);

        NewsSource::create([
            'name' => 'The Verge',
            'base_url' => 'https://www.theverge.com',
            'source_type' => 'website',
            'crawl_interval_minutes' => 120,
            'is_active' => true,
        ]);

        NewsSource::create([
            'name' => 'Wired',
            'base_url' => 'https://www.wired.com',
            'source_type' => 'website',
            'crawl_interval_minutes' => 240,
            'is_active' => false,
        ]);

        // Additional RSS sources
        NewsSource::create([
            'name' => 'Associated Press',
            'base_url' => 'https://apnews.com',
            'source_type' => 'rss',
            'crawl_interval_minutes' => 60,
            'is_active' => true,
        ]);

        NewsSource::create([
            'name' => 'The New York Times',
            'base_url' => 'https://www.nytimes.com',
            'source_type' => 'rss',
            'crawl_interval_minutes' => 90,
            'is_active' => true,
        ]);
    }
}

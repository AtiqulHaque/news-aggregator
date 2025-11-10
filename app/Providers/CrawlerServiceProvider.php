<?php

namespace App\Providers;

use App\Services\Crawlers\ApiCrawlerService;
use App\Services\Crawlers\BbcNewsCrawlerService;
use App\Services\Crawlers\CnnNewsCrawlerService;
use App\Services\Crawlers\CrawlerServiceManager;
use App\Services\Crawlers\GenericWebsiteCrawlerService;
use App\Services\Crawlers\RssFeedCrawlerService;
use Illuminate\Support\ServiceProvider;

class CrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the manager as singleton
        $this->app->singleton(CrawlerServiceManager::class, function ($app) {
            $manager = new CrawlerServiceManager();

            // Register all crawlers in priority order (highest first)
            $manager->register(new BbcNewsCrawlerService());
            $manager->register(new CnnNewsCrawlerService());
            $manager->register(new RssFeedCrawlerService());
            $manager->register(new ApiCrawlerService());
            $manager->register(new GenericWebsiteCrawlerService());

            return $manager;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}


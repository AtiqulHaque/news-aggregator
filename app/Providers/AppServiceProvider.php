<?php

namespace App\Providers;

use App\Repositories\CampaignRepository;
use App\Repositories\CampaignSourceRepository;
use App\Repositories\CrawlJobRepository;
use App\Repositories\NewsSourceRepository;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Contracts\CampaignSourceRepositoryInterface;
use App\Repositories\Contracts\CrawlJobRepositoryInterface;
use App\Repositories\Contracts\NewsSourceRepositoryInterface;
use App\Services\Crawlers\CrawlerServiceManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CampaignRepositoryInterface::class, CampaignRepository::class);
        $this->app->bind(NewsSourceRepositoryInterface::class, NewsSourceRepository::class);
        $this->app->bind(CrawlJobRepositoryInterface::class, CrawlJobRepository::class);
        $this->app->bind(CampaignSourceRepositoryInterface::class, CampaignSourceRepository::class);

        // Register crawler service provider
        $this->app->register(\App\Providers\CrawlerServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define authorization gate for Log Viewer
        // In production, you should restrict this to authenticated users only
        Gate::define('viewLogViewer', function ($user = null) {
            // Allow access in local/development environment
            if (app()->environment('local', 'development')) {
                return true;
            }

            // In production, require authentication
            // You can customize this logic based on your needs
            // For example: return $user !== null;
            return $user !== null;
        });
    }
}

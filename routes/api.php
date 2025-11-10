<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignSourceController;
use App\Http\Controllers\CrawlJobController;
use App\Http\Controllers\NewsSourceController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Campaign routes
Route::get('campaigns/status/{status}', [CampaignController::class, 'getByStatus']);
Route::apiResource('campaigns', CampaignController::class);

// News Source routes
Route::get('news-sources/type/{type}', [NewsSourceController::class, 'getByType']);
Route::get('news-sources/active/list', [NewsSourceController::class, 'getActive']);
Route::apiResource('news-sources', NewsSourceController::class);

// Crawl Job routes
Route::get('crawl-jobs/status/{status}', [CrawlJobController::class, 'getByStatus']);
Route::get('crawl-jobs/campaign/{campaignId}', [CrawlJobController::class, 'getByCampaign']);
Route::get('crawl-jobs/source/{sourceId}', [CrawlJobController::class, 'getBySource']);
Route::apiResource('crawl-jobs', CrawlJobController::class);

// Campaign Source routes
Route::get('campaign-sources', [CampaignSourceController::class, 'index']);
Route::post('campaign-sources', [CampaignSourceController::class, 'store']);
Route::get('campaign-sources/{id}', [CampaignSourceController::class, 'show']);
Route::delete('campaign-sources/{id}', [CampaignSourceController::class, 'destroy']);
Route::get('campaigns/{campaignId}/sources', [CampaignSourceController::class, 'getByCampaign']);
Route::get('news-sources/{sourceId}/campaigns', [CampaignSourceController::class, 'getBySource']);
Route::post('campaigns/{campaignId}/sources/{sourceId}', [CampaignSourceController::class, 'attach']);
Route::delete('campaigns/{campaignId}/sources/{sourceId}', [CampaignSourceController::class, 'detach']);

// Search routes
Route::get('search', [SearchController::class, 'search']);
Route::get('search/suggest', [SearchController::class, 'suggest']);
Route::get('search/filters', [SearchController::class, 'filters']);


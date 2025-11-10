<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule crawl jobs for active campaigns
// This runs every minute - adjust frequency as needed
Schedule::command('campaigns:schedule-crawl')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

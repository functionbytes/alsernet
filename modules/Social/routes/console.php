<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your console commands schedules
| for the Social module. Laravel makes it easy to schedule commands.
|
*/

// Publish scheduled posts every minute
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('social-publish-scheduled')
    ->description('Publish scheduled social media posts');

// Sync post stats every hour
Schedule::command('social:sync-stats')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('social-sync-stats')
    ->description('Sync post statistics from social networks');

// Scan social listening keywords every 15 minutes
Schedule::command('social:scan-listening')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('social-listening-scan')
    ->description('Scan social networks for listening keywords');

// Fetch RSS feeds every hour
Schedule::command('social:fetch-rss-feeds')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('rss-feeds-fetch')
    ->description('Fetch and process RSS feeds for content');

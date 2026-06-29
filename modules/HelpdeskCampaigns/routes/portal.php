<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCampaigns\Http\Controllers\Public\ImpressionTrackingController;

/*
 * Public tracking endpoints for campaign impressions and clicks.
 * Mounted at /helpdesk/campaigns/track/* by HelpdeskCampaignsServiceProvider
 * with rate limiting (no auth required — these are called from public widgets).
 */

Route::post('/view', [ImpressionTrackingController::class, 'recordView'])
    ->name('view');

Route::post('/click/{impressionUuid}', [ImpressionTrackingController::class, 'recordClick'])
    ->where('impressionUuid', '[0-9a-f-]{36}')
    ->name('click');

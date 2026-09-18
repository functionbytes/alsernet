<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCampaigns\Http\Controllers\Api\CampaignsApiController;

/*
 * REST API for HelpdeskCampaigns. Mounted at /api/v1/helpdesk/campaigns by
 * HelpdeskCampaignsServiceProvider with Sanctum auth + 60req/min throttle.
 */

Route::get('/', [CampaignsApiController::class, 'index'])->name('index');
Route::post('/', [CampaignsApiController::class, 'store'])->name('store');
Route::get('/{campaign}', [CampaignsApiController::class, 'show'])->name('show');
Route::put('/{campaign}', [CampaignsApiController::class, 'update'])->name('update');
Route::delete('/{campaign}', [CampaignsApiController::class, 'destroy'])->name('destroy');

Route::post('/{campaign}/publish', [CampaignsApiController::class, 'publish'])->name('publish');
Route::post('/{campaign}/pause', [CampaignsApiController::class, 'pause'])->name('pause');
Route::post('/{campaign}/resume', [CampaignsApiController::class, 'resume'])->name('resume');
Route::post('/{campaign}/end', [CampaignsApiController::class, 'end'])->name('end');

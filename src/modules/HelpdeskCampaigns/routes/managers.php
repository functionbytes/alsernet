<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCampaigns\Http\Controllers\Managers\CampaignsController;

Route::middleware('integration.enabled:campaigns')->group(function () {

    // Campaigns
    Route::get('/campaigns', [CampaignsController::class, 'index'])->name('helpdesk.campaigns.index');
    Route::get('/campaigns/templates', [CampaignsController::class, 'templates'])->name('helpdesk.campaigns.templates');
    Route::get('/campaigns/create', [CampaignsController::class, 'create'])->name('helpdesk.campaigns.create');
    Route::post('/campaigns', [CampaignsController::class, 'store'])->name('helpdesk.campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignsController::class, 'show'])->name('helpdesk.campaigns.show');
    Route::get('/campaigns/{campaign}/edit', [CampaignsController::class, 'edit'])->name('helpdesk.campaigns.edit');
    Route::put('/campaigns/{campaign}', [CampaignsController::class, 'update'])->name('helpdesk.campaigns.update');
    Route::delete('/campaigns/{campaign}', [CampaignsController::class, 'destroy'])->name('helpdesk.campaigns.destroy');
    Route::post('/campaigns/{campaign}/publish', [CampaignsController::class, 'publish'])->name('helpdesk.campaigns.publish');
    Route::post('/campaigns/{campaign}/pause', [CampaignsController::class, 'pause'])->name('helpdesk.campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [CampaignsController::class, 'resume'])->name('helpdesk.campaigns.resume');
    Route::post('/campaigns/{campaign}/end', [CampaignsController::class, 'end'])->name('helpdesk.campaigns.end');
    Route::get('/campaigns/{campaign}/statistics', [CampaignsController::class, 'statistics'])->name('helpdesk.campaigns.statistics');
    Route::get('/campaigns/{campaign}/statistics/timeline', [CampaignsController::class, 'statisticsTimeline'])->name('helpdesk.campaigns.statistics.timeline');
    Route::get('/campaigns/{campaign}/activity', [CampaignsController::class, 'activity'])->name('helpdesk.campaigns.activity');
    Route::get('/campaigns/{campaign}/statistics/export', [CampaignsController::class, 'exportStatistics'])->name('helpdesk.campaigns.statistics.export');
    Route::post('/campaigns/{campaign}/duplicate', [CampaignsController::class, 'duplicate'])->name('helpdesk.campaigns.duplicate');

    // Bulk + approval workflow
    Route::post('/campaigns/bulk-action', [CampaignsController::class, 'bulkAction'])->name('helpdesk.campaigns.bulk-action');
    Route::post('/campaigns/{campaign}/submit-for-approval', [CampaignsController::class, 'submitForApproval'])->name('helpdesk.campaigns.submit-for-approval');
    Route::post('/campaigns/{campaign}/approve', [CampaignsController::class, 'approve'])->name('helpdesk.campaigns.approve');
    Route::post('/campaigns/{campaign}/reject', [CampaignsController::class, 'reject'])->name('helpdesk.campaigns.reject');
});

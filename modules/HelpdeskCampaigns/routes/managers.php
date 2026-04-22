<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCampaigns\Http\Controllers\Managers\CampaignsController;

Route::group(['prefix' => ''], function () {

    // Campaigns
    Route::get('/campaigns', [CampaignsController::class, 'index'])->name('manager.helpdesk-campaigns.index');
    Route::get('/campaigns/templates', [CampaignsController::class, 'templates'])->name('manager.helpdesk-campaigns.templates');
    Route::get('/campaigns/create', [CampaignsController::class, 'create'])->name('manager.helpdesk-campaigns.create');
    Route::post('/campaigns', [CampaignsController::class, 'store'])->name('manager.helpdesk-campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignsController::class, 'show'])->name('manager.helpdesk-campaigns.show');
    Route::get('/campaigns/{campaign}/edit', [CampaignsController::class, 'edit'])->name('manager.helpdesk-campaigns.edit');
    Route::put('/campaigns/{campaign}', [CampaignsController::class, 'update'])->name('manager.helpdesk-campaigns.update');
    Route::delete('/campaigns/{campaign}', [CampaignsController::class, 'destroy'])->name('manager.helpdesk-campaigns.destroy');
    Route::post('/campaigns/{campaign}/publish', [CampaignsController::class, 'publish'])->name('manager.helpdesk-campaigns.publish');
    Route::post('/campaigns/{campaign}/pause', [CampaignsController::class, 'pause'])->name('manager.helpdesk-campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [CampaignsController::class, 'resume'])->name('manager.helpdesk-campaigns.resume');
    Route::post('/campaigns/{campaign}/end', [CampaignsController::class, 'end'])->name('manager.helpdesk-campaigns.end');
    Route::get('/campaigns/{campaign}/statistics', [CampaignsController::class, 'statistics'])->name('manager.helpdesk-campaigns.statistics');
    Route::post('/campaigns/{campaign}/duplicate', [CampaignsController::class, 'duplicate'])->name('manager.helpdesk-campaigns.duplicate');
});

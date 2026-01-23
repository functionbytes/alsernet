<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailrelay\Http\Controllers\Api\EmailValidationController;
use Modules\Mailrelay\Http\Controllers\Api\ImportController;
use Modules\Mailrelay\Http\Controllers\Api\ListController;
use Modules\Mailrelay\Http\Controllers\Api\NewsletterController;
use Modules\Mailrelay\Http\Controllers\Mailrelay\CampaignController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file defines all API endpoints for the Email Validator system.
| Reference: FASE 10 - API Routes Configuration
|
*/

// Email Validation Routes
Route::prefix('validation')->group(function () {
    Route::post('validate', [EmailValidationController::class, 'validateEmail'])
        ->name('validation.validate');

    Route::post('validate-bulk', [EmailValidationController::class, 'validateBulk'])
        ->name('validation.validate-bulk');
});

// Newsletter Subscription Routes
Route::prefix('newsletter')->group(function () {
    Route::post('subscribe', [NewsletterController::class, 'subscribe'])
        ->name('newsletter.subscribe');

    Route::post('unsubscribe', [NewsletterController::class, 'unsubscribe'])
        ->name('newsletter.unsubscribe');

    Route::get('status', [NewsletterController::class, 'status'])
        ->name('newsletter.status');

    Route::get('subscribers', [NewsletterController::class, 'index'])
        ->name('newsletter.subscribers');
});

// Import Routes
Route::prefix('imports')->group(function () {
    Route::post('upload', [ImportController::class, 'upload'])
        ->name('imports.upload');

    Route::get('{id}/status', [ImportController::class, 'status'])
        ->name('imports.status');

    Route::get('{id}/report', [ImportController::class, 'report'])
        ->name('imports.report');
});

// Lists Routes
Route::prefix('lists')->group(function () {
    Route::get('/', [ListController::class, 'index'])
        ->name('lists.index');

    Route::post('/', [ListController::class, 'store'])
        ->name('lists.store');

    Route::get('{list}', [ListController::class, 'show'])
        ->name('lists.show');

    Route::patch('{list}', [ListController::class, 'update'])
        ->name('lists.update');

    Route::delete('{list}', [ListController::class, 'destroy'])
        ->name('lists.destroy');
});

// Campaign Routes
Route::prefix('campaigns')->group(function () {
    // CRUD Operations
    Route::get('/', [CampaignController::class, 'index'])
        ->name('campaigns.index');

    Route::post('/', [CampaignController::class, 'create'])
        ->name('campaigns.create');

    Route::get('{id}', [CampaignController::class, 'get'])
        ->name('campaigns.get');

    Route::patch('{id}', [CampaignController::class, 'update'])
        ->name('campaigns.update');

    Route::delete('{id}', [CampaignController::class, 'delete'])
        ->name('campaigns.delete');

    // Campaign Actions
    Route::post('{id}/send', [CampaignController::class, 'send'])
        ->name('campaigns.send');

    Route::post('{id}/send-all', [CampaignController::class, 'sendAll'])
        ->name('campaigns.send-all');

    Route::post('{id}/send-test', [CampaignController::class, 'sendTest'])
        ->name('campaigns.send-test');

    // Analytics
    Route::get('{id}/analytics', [CampaignController::class, 'analytics'])
        ->name('campaigns.analytics');
});

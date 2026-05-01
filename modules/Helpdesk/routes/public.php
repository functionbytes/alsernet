<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\FeedbackController;
use Modules\Helpdesk\Http\Controllers\HealthController;
use Modules\Helpdesk\Http\Controllers\HelpCenterPublicController;
use Modules\Helpdesk\Http\Controllers\StatusPageController;
use Modules\Helpdesk\Http\Controllers\SurveyController;

Route::middleware(['web', 'throttle:helpdesk-feedback'])
    ->prefix('helpdesk/feedback')
    ->name('helpdesk.feedback.')
    ->group(function () {
        Route::get('{ticketNumber}', [FeedbackController::class, 'show'])->name('show');
        Route::post('{ticketNumber}', [FeedbackController::class, 'submit'])->name('submit');
    });

Route::middleware(['web', 'throttle:helpdesk-customer-portal'])
    ->prefix('helpcenter')
    ->name('helpcenter.')
    ->group(function () {
        Route::get('/', [HelpCenterPublicController::class, 'index'])->name('index');
        Route::get('search', [HelpCenterPublicController::class, 'search'])->name('search')->middleware('throttle:helpdesk-search');
        Route::get('articles/{slug}', [HelpCenterPublicController::class, 'show'])->name('show');
    });

// Status page (public)
Route::middleware(['web'])
    ->group(function () {
        Route::get('status', [StatusPageController::class, 'index'])->name('helpdesk.status.index');
        Route::get('status.json', [StatusPageController::class, 'json'])->name('helpdesk.status.json');
    });

// Health check endpoint (no auth, no CSRF — para load balancer)
Route::get('helpdesk/health', [HealthController::class, 'check'])
    ->withoutMiddleware(['web'])
    ->name('helpdesk.health');

// Surveys (public magic-link)
Route::middleware(['web', 'throttle:30,1'])
    ->prefix('survey')
    ->name('helpdesk.survey.')
    ->group(function () {
        Route::get('{token}', [SurveyController::class, 'show'])->name('show');
        Route::post('{token}', [SurveyController::class, 'submit'])->name('submit');
    });

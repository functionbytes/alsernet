<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\FeedbackController;
use Modules\Helpdesk\Http\Controllers\HelpCenterPublicController;

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

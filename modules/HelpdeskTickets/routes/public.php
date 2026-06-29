<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Http\Controllers\PublicTicketFormController;

/*
 * Public feedback URLs. Loaded by HelpdeskTicketsServiceProvider (no auth).
 * URLs and route names match the previous Helpdesk-owned ones so that links
 * already sent in customer emails keep working without any redirect.
 */
Route::middleware(['web', 'throttle:helpdesk-feedback'])
    ->prefix('helpdesk/feedback')
    ->name('helpdesk.feedback.')
    ->group(function () {
        Route::get('{ticketNumber}', [FeedbackController::class, 'show'])->name('show');
        Route::post('{ticketNumber}', [FeedbackController::class, 'submit'])->name('submit');
    });

// Public ticket form API (widget, embeds, external integrations)
Route::middleware(['api', 'throttle:30,1'])
    ->prefix('hd/api/ticket-forms')
    ->name('helpdesk.public.ticket-forms.')
    ->group(function () {
        Route::get('{slug}', [PublicTicketFormController::class, 'show'])->name('show');
        Route::post('{slug}/submit', [PublicTicketFormController::class, 'submit'])
            ->middleware('throttle:10,1')
            ->name('submit');
    });

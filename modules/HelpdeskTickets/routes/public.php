<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Http\Controllers\PublicTicketFormController;
use Modules\HelpdeskTickets\Http\Controllers\SharedTicketController;

/*
 * Public feedback URLs. Loaded by HelpdeskTicketsServiceProvider (no auth).
 * URLs and route names match the previous Helpdesk-owned ones. Since the
 * ticket number is sequential, both routes now require a valid signature
 * (URL::signedRoute) — unsigned legacy links get a 403 instead of exposing
 * other customers' tickets.
 */
Route::middleware(['web', 'throttle:helpdesk-feedback', 'signed'])
    ->prefix('helpdesk/feedback')
    ->name('helpdesk.feedback.')
    ->group(function () {
        Route::get('{ticketNumber}', [FeedbackController::class, 'show'])->name('show');
        Route::post('{ticketNumber}', [FeedbackController::class, 'submit'])->name('submit');
    });

/*
 * Enlace de solo lectura para compartir un ticket con el cliente
 * ("Ver como el cliente" del mockup, versión segura: enlace firmado de 30
 * días en vez de suplantar la sesión del agente — ver docblock de
 * SharedTicketController). Nunca requiere login ni toca portal_customer_id.
 */
Route::middleware(['web', 'throttle:60,1440', 'signed'])
    ->prefix('helpdesk/shared')
    ->name('helpdesk.shared-ticket.')
    ->group(function () {
        Route::get('{ticketNumber}', [SharedTicketController::class, 'show'])->name('show');
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

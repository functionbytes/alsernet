<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\CsatController;
use Modules\Helpdesk\Http\Controllers\HealthController;
use Modules\Helpdesk\Http\Controllers\Public\ContinueController;
use Modules\Helpdesk\Http\Controllers\Public\PublicSimulatorController;
use Modules\Helpdesk\Http\Controllers\StatusPageController;
use Modules\Helpdesk\Http\Controllers\SurveyController;

// /helpdesk/feedback/{ticketNumber} routes are owned by HelpdeskTickets
// (see modules/HelpdeskTickets/routes/public.php).

// Status page (public)
Route::middleware(['web'])
    ->group(function () {
        Route::get('status', [StatusPageController::class, 'index'])->name('helpdesk.status.index');
        Route::get('status.json', [StatusPageController::class, 'json'])->name('helpdesk.status.json');
    });

// Health check endpoint (no auth, no CSRF — para load balancer)
Route::get('helpdesk/health', [HealthController::class, 'check'])
    ->withoutMiddleware(['web'])
    ->middleware('throttle:60,1')
    ->name('helpdesk.health');

// Surveys (public magic-link)
Route::middleware(['web', 'throttle:30,1'])
    ->prefix('survey')
    ->name('helpdesk.survey.')
    ->group(function () {
        Route::get('{token}', [SurveyController::class, 'show'])->name('show');
        Route::post('{token}', [SurveyController::class, 'submit'])->name('submit');
    });

// CSAT surveys (public magic-link)
Route::middleware(['web', 'throttle:10,5'])
    ->prefix('helpdesk/csat')
    ->name('helpdesk.csat.')
    ->group(function () {
        Route::get('{token}', [CsatController::class, 'show'])->name('show');
        Route::post('{token}', [CsatController::class, 'submit'])->name('submit');
    });

// Continue conversation magic-link (sent by email when widget closes)
Route::middleware(['web', 'throttle:5,1'])
    ->get('hd/continue/{token}', [ContinueController::class, 'show'])
    ->name('public.helpdesk.continue');

// Public multi-channel conversation simulator.
// Gated by config('helpdesk.simulator_public_enabled') in the controller (404 in
// production). No auth: per-conversation writes are authorized by an opaque token.
Route::middleware(['web', 'throttle:60,1'])
    ->prefix('helpdesk/sim')
    ->name('helpdesk.sim.')
    ->group(function () {
        Route::get('/', [PublicSimulatorController::class, 'index'])->name('index');
        Route::get('/sessions', [PublicSimulatorController::class, 'sessions'])
            ->middleware('throttle:30,1')->name('sessions');
        Route::post('/start', [PublicSimulatorController::class, 'start'])
            ->middleware('throttle:15,1')->name('start');
        Route::post('/{conversation}/inbound', [PublicSimulatorController::class, 'inbound'])
            ->middleware('throttle:40,1')->name('inbound');
        Route::post('/{conversation}/inject', [PublicSimulatorController::class, 'inject'])
            ->middleware('throttle:30,1')->name('inject');
        Route::get('/{conversation}/messages', [PublicSimulatorController::class, 'messages'])->name('messages');
        Route::get('/lookup', [PublicSimulatorController::class, 'lookup'])
            ->middleware('throttle:30,1')->name('lookup');
        Route::post('/{conversation}/attachment', [PublicSimulatorController::class, 'attachment'])
            ->middleware('throttle:20,1')->name('attachment');
        Route::get('/{conversation}/csat', [PublicSimulatorController::class, 'csat'])->name('csat');
    });

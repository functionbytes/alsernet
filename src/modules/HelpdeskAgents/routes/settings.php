<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAgents\Http\Controllers\Managers\Settings\ScheduleController;

// Schedule (shifts, vacations, on-call) — registered under settings.helpdesk.schedule.*
// to keep public URLs and route names stable after the refactor.
Route::prefix('schedule')->name('schedule.')->middleware('integration.enabled:agents')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])->name('index');
    Route::post('shifts', [ScheduleController::class, 'storeShift'])->name('shifts.store');
    Route::delete('shifts/{shift}', [ScheduleController::class, 'destroyShift'])->name('shifts.destroy');
    Route::post('vacations', [ScheduleController::class, 'storeVacation'])->name('vacations.store');
    Route::delete('vacations/{vacation}', [ScheduleController::class, 'destroyVacation'])->name('vacations.destroy');
    Route::post('oncall', [ScheduleController::class, 'storeOncall'])->name('oncall.store');
    Route::delete('oncall/{oncall}', [ScheduleController::class, 'destroyOncall'])->name('oncall.destroy');
});

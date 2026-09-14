<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskSla\Http\Controllers\Managers\HolidaysController;
use Modules\HelpdeskSla\Http\Controllers\Managers\SlaBreachesController;

/*
| HelpdeskSla manager routes.
|
| Mounted by HelpdeskSlaServiceProvider with prefix 'panel/helpdesksla' and
| middleware ['web', 'auth']. {breach} resolves Modules\HelpdeskSla\Models\ConversationSlaBreach.
*/
Route::name('helpdesksla.')
    ->middleware(['can:helpdesksla.view', 'integration.enabled:sla'])
    ->group(function () {
        Route::get('breaches', [SlaBreachesController::class, 'index'])->name('breaches.index');
        Route::get('breaches/data', [SlaBreachesController::class, 'data'])->name('breaches.data');
        Route::post('breaches/{breach}/resolve', [SlaBreachesController::class, 'resolve'])
            ->middleware('can:helpdesksla.manage')
            ->name('breaches.resolve');

        // Calendario de festivos (días no laborables del motor de horas hábiles)
        Route::get('holidays', [HolidaysController::class, 'index'])->name('holidays.index');
        Route::post('holidays', [HolidaysController::class, 'store'])
            ->middleware('can:helpdesksla.manage')
            ->name('holidays.store');
        Route::delete('holidays/{holiday}', [HolidaysController::class, 'destroy'])
            ->middleware('can:helpdesksla.manage')
            ->name('holidays.destroy');
    });

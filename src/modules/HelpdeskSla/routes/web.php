<?php

use Illuminate\Support\Facades\Route;
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
    });

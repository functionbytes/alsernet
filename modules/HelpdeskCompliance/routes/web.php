<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskCompliance\Http\Controllers\Managers\ComplianceController;

/*
| HelpdeskCompliance manager routes.
|
| Mounted by HelpdeskComplianceServiceProvider with prefix 'panel/helpdeskcompliance'
| and middleware ['web', 'auth'].
*/
Route::name('helpdeskcompliance.')
    ->middleware(['can:helpdeskcompliance.view', 'integration.enabled:compliance'])
    ->group(function () {
        Route::get('requests', [ComplianceController::class, 'index'])->name('requests.index');
        Route::get('requests/data', [ComplianceController::class, 'data'])->name('requests.data');
    });

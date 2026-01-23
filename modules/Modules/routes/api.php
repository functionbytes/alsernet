<?php

use Illuminate\Support\Facades\Route;
use Modules\Modules\Http\Controllers\ModulesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('modules', ModulesController::class)->names('modules');

    // Additional modules management operations
    Route::post('/modules/install', [ModulesController::class, 'install'])->name('modules.install');
    Route::post('/modules/{moduleAlias}/enable', [ModulesController::class, 'enable'])->name('modules.enable');
    Route::post('/modules/{moduleAlias}/disable', [ModulesController::class, 'disable'])->name('modules.disable');
    Route::post('/modules/{moduleAlias}/uninstall', [ModulesController::class, 'uninstall'])->name('modules.uninstall');
});

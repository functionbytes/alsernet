<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Managers\ProductReturnRuleController;

/*
|--------------------------------------------------------------------------
| Manager Return Settings Routes
|--------------------------------------------------------------------------
|
| Routes for manager configuration and backups related to returns
|
*/

// Product return rules management
Route::group(['prefix' => 'rules', 'name' => 'rules.'], function () {
    Route::get('/', [ProductReturnRuleController::class, 'index'])->name('index');
    Route::get('/create', [ProductReturnRuleController::class, 'create'])->name('create');
    Route::post('/', [ProductReturnRuleController::class, 'store'])->name('store');
    Route::get('/{returnRule}', [ProductReturnRuleController::class, 'show'])->name('show');
    Route::get('/{returnRule}/edit', [ProductReturnRuleController::class, 'edit'])->name('edit');
    Route::post('/{returnRule}', [ProductReturnRuleController::class, 'update'])->name('update');
    Route::delete('/{returnRule}', [ProductReturnRuleController::class, 'destroy'])->name('destroy');
    Route::post('/{returnRule}/toggle-status', [ProductReturnRuleController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{returnRule}/clone', [ProductReturnRuleController::class, 'clone'])->name('clone');
    Route::get('/export/all', [ProductReturnRuleController::class, 'export'])->name('export');
    Route::post('/{returnRule}/test', [ProductReturnRuleController::class, 'test'])->name('test');
});

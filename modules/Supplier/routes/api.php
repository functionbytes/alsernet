<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\Api\AutomationApiController;
use Modules\Supplier\Http\Controllers\Api\PromptSelectionApiController;
use Modules\Supplier\Http\Controllers\Api\SupplierApiController;
use Modules\Supplier\Http\Controllers\Api\SupplierProductApiController;
use Modules\Supplier\Http\Controllers\Api\SyncApiController;

// Prompt selection (AI content generation)
Route::prefix('prompts')->name('prompts.')->middleware(['can:suppliers.view.content'])->group(function () {
    Route::post('select', [PromptSelectionApiController::class, 'select'])->name('select');
    Route::get('explain', [PromptSelectionApiController::class, 'explain'])->name('explain');
    Route::post('batch-select', [PromptSelectionApiController::class, 'batchSelect'])->name('batch-select');
    Route::get('health', [PromptSelectionApiController::class, 'health'])->name('health');
});

// Suppliers
Route::middleware(['can:suppliers.view'])->group(function () {
    Route::get('/', [SupplierApiController::class, 'index'])->name('index');
    Route::get('{uid}', [SupplierApiController::class, 'show'])->name('show');
});
Route::post('{uid}/toggle', [SupplierApiController::class, 'toggle'])
    ->middleware(['can:suppliers.configure'])
    ->name('toggle');

// Products
Route::prefix('products')->name('products.')->group(function () {
    Route::middleware(['can:suppliers.view.products'])->group(function () {
        Route::get('/', [SupplierProductApiController::class, 'index'])->name('index');
        Route::get('{id}', [SupplierProductApiController::class, 'show'])->name('show');
    });
    Route::patch('{id}', [SupplierProductApiController::class, 'update'])
        ->middleware(['can:suppliers.products.manage'])
        ->name('update');
});

// Sync
Route::prefix('sync')->name('sync.')->group(function () {
    Route::middleware(['can:suppliers.sync.status'])->group(function () {
        Route::get('batches', [SyncApiController::class, 'batches'])->name('batches.index');
        Route::get('batches/{id}', [SyncApiController::class, 'showBatch'])->name('batches.show');
    });
    Route::post('trigger', [SyncApiController::class, 'trigger'])
        ->middleware(['can:suppliers.sync.execute'])
        ->name('trigger');
});

// Automation
Route::prefix('automation')->name('automation.')->group(function () {
    Route::middleware(['can:suppliers.view.automation'])->group(function () {
        Route::get('workflows', [AutomationApiController::class, 'workflows'])->name('workflows.index');
        Route::get('executions', [AutomationApiController::class, 'executions'])->name('executions.index');
    });
    Route::middleware(['can:suppliers.automation.manage'])->group(function () {
        Route::post('workflows/{uid}/run', [AutomationApiController::class, 'run'])->name('workflows.run');
        Route::post('executions/{id}/retry', [AutomationApiController::class, 'retry'])->name('executions.retry');
    });
});

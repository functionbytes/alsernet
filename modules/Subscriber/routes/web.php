<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriber\Http\Controllers\SubscribersConditionsController;
use Modules\Subscriber\Http\Controllers\SubscribersController;
use Modules\Subscriber\Http\Controllers\SubscribersListsController;
use Modules\Subscriber\Http\Controllers\SubscribersReportController;

// Subscribers CRUD
Route::get('/', [SubscribersController::class, 'index'])->name('index');
Route::get('/create', [SubscribersController::class, 'create'])->name('create');
Route::post('/update', [SubscribersController::class, 'update'])->name('update');
Route::get('/edit/{uid}', [SubscribersController::class, 'edit'])->name('edit');
Route::get('/view/{uid}', [SubscribersController::class, 'view'])->name('view');
Route::get('/destroy/{uid}', [SubscribersController::class, 'destroy'])->name('destroy');
Route::get('/logs/{slack}', [SubscribersController::class, 'logs'])->name('logs');

// Imports
Route::prefix('imports')->name('imports.')->group(function () {
    Route::get('/create', [SubscribersController::class, 'createImport'])->name('create');
    Route::get('/{import_uid}', [SubscribersController::class, 'createImports'])->name('show');
    Route::post('/{import_uid}/dispatch', [SubscribersController::class, 'dispatchImportListsJobs'])->name('dispatch');
    Route::get('/{job_uid}/progress', [SubscribersController::class, 'importListsProgress'])->name('progress');
    Route::get('/{job_uid}/log/download', [SubscribersController::class, 'downloadImportListsLog'])->name('log.download');
    Route::post('/{job_uid}/cancel', [SubscribersController::class, 'cancelImportLists'])->name('cancel');
});

// Lists
Route::prefix('lists')->name('lists.')->group(function () {
    Route::get('/', [SubscribersListsController::class, 'index'])->name('index');
    Route::get('/create', [SubscribersListsController::class, 'create'])->name('create');
    Route::post('/store', [SubscribersListsController::class, 'store'])->name('store');
    Route::post('/update', [SubscribersListsController::class, 'update'])->name('update');
    Route::get('/edit/{uid}', [SubscribersListsController::class, 'edit'])->name('edit');
    Route::get('/details/{uid}', [SubscribersListsController::class, 'details'])->name('details');
    Route::get('/categories/{uid}', [SubscribersListsController::class, 'categories'])->name('categories');
    Route::post('/categories/update', [SubscribersListsController::class, 'updateCategories'])->name('categories.update');
    Route::get('/components/{uid}', [SubscribersListsController::class, 'includes'])->name('components');
    Route::post('/components/update', [SubscribersListsController::class, 'updateIncludes'])->name('components.update');
    Route::get('/destroy/{uid}', [SubscribersListsController::class, 'destroy'])->name('destroy');
    Route::get('/{uid}', [SubscribersListsController::class, 'list'])->name('show');

    // Reports
    Route::get('/reports', [SubscribersReportController::class, 'report'])->name('reports');
    Route::get('/report/generate', [SubscribersReportController::class, 'generate'])->name('report.generate');
});

// Conditions
Route::prefix('conditions')->name('conditions.')->group(function () {
    Route::get('/', [SubscribersConditionsController::class, 'index'])->name('index');
    Route::get('/create', [SubscribersConditionsController::class, 'create'])->name('create');
    Route::post('/store', [SubscribersConditionsController::class, 'store'])->name('store');
    Route::post('/update', [SubscribersConditionsController::class, 'update'])->name('update');
    Route::get('/edit/{uid}', [SubscribersConditionsController::class, 'edit'])->name('edit');
    Route::get('/destroy/{uid}', [SubscribersConditionsController::class, 'destroy'])->name('destroy');
});

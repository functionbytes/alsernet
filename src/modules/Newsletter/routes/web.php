<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\SubscriberController;

Route::get('/', [SubscriberController::class, 'index'])->name('index');
Route::post('/bulk-action', [SubscriberController::class, 'bulkAction'])->name('bulk-action');
Route::get('/export', [SubscriberController::class, 'export'])->name('export');
Route::delete('/{subscriber}', [SubscriberController::class, 'destroy'])->name('destroy');

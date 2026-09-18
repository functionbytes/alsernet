<?php

use Modules\Queue\Http\Controllers\Settings\QueueDashboardController;

Route::get('/', [QueueDashboardController::class, 'index'])->name('dashboard');
Route::get('/failed-jobs', [QueueDashboardController::class, 'failedJobs'])->name('failed-jobs');
Route::get('/pending-jobs', [QueueDashboardController::class, 'pendingJobs'])->name('pending-jobs');
Route::get('/stats', [QueueDashboardController::class, 'stats'])->name('stats');
Route::post('/failed-jobs/retry-all', [QueueDashboardController::class, 'retryAll'])->name('failed-jobs.retry-all');
Route::post('/failed-jobs/bulk-retry', [QueueDashboardController::class, 'bulkRetry'])->name('failed-jobs.bulk-retry');
Route::delete('/failed-jobs/bulk-delete', [QueueDashboardController::class, 'bulkDelete'])->name('failed-jobs.bulk-delete');
Route::post('/failed-jobs/{uuid}/retry', [QueueDashboardController::class, 'retryJob'])->name('failed-jobs.retry');
Route::delete('/failed-jobs/{uuid}', [QueueDashboardController::class, 'deleteFailedJob'])->name('failed-jobs.delete');

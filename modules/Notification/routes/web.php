<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationPreferenceController;
use Modules\Notification\Http\Controllers\NotificationsController;

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
|
| Prefix: /notifications (applied by NotificationServiceProvider)
| Name: notifications.* (applied by NotificationServiceProvider)
| Middleware: web, auth (applied by NotificationServiceProvider)
|
*/

Route::get('/', [NotificationsController::class, 'index'])->name('index');
Route::post('/{id}/mark-as-read', [NotificationsController::class, 'markAsRead'])->name('markAsRead');
Route::post('/mark-all-as-read', [NotificationsController::class, 'markAllAsRead'])->name('markAllAsRead');
Route::post('/bulk-action', [NotificationsController::class, 'bulkAction'])->name('bulk-action');
Route::delete('/bulk', [NotificationsController::class, 'bulkDestroy'])->name('bulk-destroy');
Route::delete('/all', [NotificationsController::class, 'destroyAll'])->name('destroy-all');
Route::delete('/{id}', [NotificationsController::class, 'destroy'])->name('destroy');

Route::get('/preferences', [NotificationPreferenceController::class, 'index'])->name('preferences.index');
Route::post('/preferences', [NotificationPreferenceController::class, 'update'])->name('preferences.update');

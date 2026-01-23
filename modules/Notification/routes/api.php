<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| Notification API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/notifications (applied by NotificationServiceProvider)
| Name: api.notifications.* (applied by NotificationServiceProvider)
| Middleware: web, auth:web (applied by NotificationServiceProvider)
|
*/

Route::get('/', [NotificationController::class, 'index'])->name('index');
Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');

// Preferencias
Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('update-preferences');

// Push Tokens
Route::post('/push-tokens', [NotificationController::class, 'registerPushToken'])->name('register-push-token');
Route::delete('/push-tokens/{id}', [NotificationController::class, 'deactivatePushToken'])->name('deactivate-push-token');

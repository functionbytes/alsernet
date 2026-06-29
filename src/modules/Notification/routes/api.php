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

// Rutas de lectura — 60 requests/min
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
    Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
});

// Rutas de escritura — 30 requests/min
Route::middleware(['throttle:30,1'])->group(function () {
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('update-preferences');
});

// Push tokens — 10 requests/min
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/push-tokens', [NotificationController::class, 'registerPushToken'])->name('register-push-token');
    Route::delete('/push-tokens/{id}', [NotificationController::class, 'deactivatePushToken'])->name('deactivate-push-token');
});

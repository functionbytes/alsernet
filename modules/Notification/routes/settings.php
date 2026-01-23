<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Settings\NotificationSettingsController;

/*
|--------------------------------------------------------------------------
| Notification Settings Routes
|--------------------------------------------------------------------------
|
| Prefix: /backups/notifications (applied by NotificationServiceProvider)
| Name: backups.notifications.* (applied by NotificationServiceProvider)
| Middleware: web, auth, role:super-admin (applied by NotificationServiceProvider)
|
*/

Route::get('/', [NotificationSettingsController::class, 'index'])->name('index');
Route::post('/update', [NotificationSettingsController::class, 'update'])->name('update');

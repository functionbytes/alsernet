<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\PushSubscriptionController;

/*
|--------------------------------------------------------------------------
| Web Push Subscription Routes
|--------------------------------------------------------------------------
|
| Prefix: /panel/push (applied by NotificationServiceProvider)
| Name: push.* (applied by NotificationServiceProvider)
| Middleware: web, auth:web (applied by NotificationServiceProvider)
|
*/

Route::post('/subscribe', [PushSubscriptionController::class, 'store'])->name('subscribe');
Route::post('/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('unsubscribe');

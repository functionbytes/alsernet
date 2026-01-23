<?php

use Modules\Auth\Http\Controllers\Settings\PasswordController;
use Modules\Auth\Http\Controllers\Settings\ProfileController;

/*
|--------------------------------------------------------------------------
| Auth Settings Routes
|--------------------------------------------------------------------------
|
| Rutas protegidas de configuración de autenticación (profile, sessions, etc.)
| Prefix: /settings/auth (aplicado por ServiceProvider)
| Name: settings.auth.* (aplicado por ServiceProvider)
| Middleware: web, auth (aplicado por ServiceProvider)
|
*/

// Profile routes
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

// Individual tab routes (maintain compatibility with existing URLs)
Route::get('/sessions', [ProfileController::class, 'sessions'])->name('sessions');
Route::get('/two-factor', [ProfileController::class, 'twoFactor'])->name('two-factor');
Route::get('/password/edit', [ProfileController::class, 'password'])->name('password.edit');
Route::get('/devices', [ProfileController::class, 'devices'])->name('devices');

// Password update route
Route::post('/password/update', [PasswordController::class, 'update'])->name('password.update');

<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Settings\PasswordController;
use Modules\Auth\Http\Controllers\Settings\ProfileController;
use Modules\Auth\Http\Controllers\Settings\SessionController;
use Modules\Auth\Http\Controllers\Settings\TwoFactorAuthenticationController;

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
Route::put('/profile/info', [ProfileController::class, 'updateInfo'])->name('profile.update-info');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.delete-avatar');

// Individual tab routes (maintain compatibility with existing URLs)
Route::get('/sessions', [ProfileController::class, 'sessions'])->name('sessions');
Route::get('/two-factor', [ProfileController::class, 'twoFactor'])->name('two-factor');
Route::get('/password/edit', [ProfileController::class, 'password'])->name('password.edit');
Route::get('/devices', [ProfileController::class, 'devices'])->name('devices');

// Password update route
Route::post('/password/update', [PasswordController::class, 'update'])->name('password.update');

// 2FA management routes (AJAX)
Route::post('/two-factor/setup', [TwoFactorAuthenticationController::class, 'setup'])->name('two-factor.setup');
Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
Route::delete('/two-factor/disable', [TwoFactorAuthenticationController::class, 'disable'])->name('two-factor.disable');
Route::post('/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateCodes'])->name('two-factor.recovery-codes');

// Session management routes (AJAX)
Route::get('/sessions/list', [SessionController::class, 'index'])->name('sessions.list');
Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
Route::post('/sessions/destroy-others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');

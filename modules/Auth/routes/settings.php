<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Admin\AuditController;
use Modules\Auth\Http\Controllers\Settings\AccountDeletionController;
use Modules\Auth\Http\Controllers\Settings\ActivityController;
use Modules\Auth\Http\Controllers\Settings\ApiTokenController;
use Modules\Auth\Http\Controllers\Settings\DeviceController;
use Modules\Auth\Http\Controllers\Settings\EmailChangeController;
use Modules\Auth\Http\Controllers\Settings\PasswordController;
use Modules\Auth\Http\Controllers\Settings\ProfileController;
use Modules\Auth\Http\Controllers\Settings\SessionController;
use Modules\Auth\Http\Controllers\Settings\TwoFactorAuthenticationController;

/*
|--------------------------------------------------------------------------
| Auth Settings Routes
|--------------------------------------------------------------------------
|
| Prefix: /panel/settings/auth (ServiceProvider)
| Name: settings.auth.* (ServiceProvider)
| Middleware: web, auth, auth.session.lock (ServiceProvider)
|
*/

// Profile routes
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::put('/profile/info', [ProfileController::class, 'updateInfo'])->name('profile.update-info');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.delete-avatar');

// Tab routes
Route::get('/sessions', [ProfileController::class, 'sessions'])->name('sessions');
Route::get('/two-factor', [ProfileController::class, 'twoFactor'])->name('two-factor');
Route::get('/password/edit', [ProfileController::class, 'password'])->name('password.edit');
Route::get('/devices', [ProfileController::class, 'devices'])->name('devices');

// Sensitive actions - deny while impersonating
Route::middleware('auth.deny-impersonating')->group(function () {
    Route::post('/password/update', [PasswordController::class, 'update'])->name('password.update');

    // 2FA management
    Route::post('/two-factor/setup', [TwoFactorAuthenticationController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/two-factor/disable', [TwoFactorAuthenticationController::class, 'disable'])->name('two-factor.disable');
    Route::post('/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateCodes'])->name('two-factor.recovery-codes');
});

Route::get('/two-factor/recovery-codes/pdf', [TwoFactorAuthenticationController::class, 'downloadRecoveryCodes'])->name('two-factor.recovery-codes.pdf');

// Session management (AJAX)
Route::get('/sessions/list', [SessionController::class, 'index'])->name('sessions.list');
Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
Route::post('/sessions/destroy-others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');

// Device management (AJAX)
Route::get('/devices/list', [DeviceController::class, 'index'])->name('devices.list');
Route::post('/devices/{id}/trust', [DeviceController::class, 'trust'])->name('devices.trust');
Route::post('/devices/{id}/revoke', [DeviceController::class, 'revoke'])->name('devices.revoke');
Route::delete('/devices/{id}', [DeviceController::class, 'destroy'])->name('devices.destroy');

// Activity log (AJAX)
Route::get('/activity/list', [ActivityController::class, 'index'])->name('activity.list');

// Personal access tokens (AJAX)
Route::get('/api-tokens/list', [ApiTokenController::class, 'index'])->name('api-tokens.list');
Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
Route::delete('/api-tokens/{id}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

// Tab page (reuses ProfileController pattern)
Route::get('/api-tokens', [ProfileController::class, 'apiTokens'])->name('api-tokens');

// Admin audit (requires auth.audit.view permission, enforced by controller)
Route::get('/audit/login-attempts', [AuditController::class, 'loginAttempts'])->name('audit.login-attempts');
Route::get('/audit/impersonations', [AuditController::class, 'impersonations'])->name('audit.impersonations');
Route::post('/audit/users/{user}/force-logout', [AuditController::class, 'forceLogout'])->name('audit.force-logout');
Route::post('/audit/users/{user}/unlock', [AuditController::class, 'unlockAccount'])->name('audit.unlock');
Route::post('/audit/users/{userId}/restore', [AuditController::class, 'restoreAccount'])->name('audit.restore');

// Email change request (confirmation comes via public route)
Route::post('/email/change', [EmailChangeController::class, 'request'])->name('email.change.request')->middleware('auth.deny-impersonating');

// Self-service account deletion (requires password + typed confirmation)
Route::delete('/account', [AccountDeletionController::class, 'destroy'])
    ->name('account.delete')
    ->middleware('auth.deny-impersonating');

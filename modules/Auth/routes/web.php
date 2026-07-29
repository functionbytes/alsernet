<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\ForgotPasswordController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\MagicLinkController;
use Modules\Auth\Http\Controllers\ResetPasswordController;
use Modules\Auth\Http\Controllers\Settings\EmailChangeController as PublicEmailChangeController;
use Modules\Auth\Http\Controllers\ValidationController;
use Modules\Auth\Http\Controllers\VerificationController;

/*
|--------------------------------------------------------------------------
| Auth Public Routes
|--------------------------------------------------------------------------
|
| Rutas públicas de autenticación (login, register, password reset)
| Middleware: web, guest
|
*/

// Login routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('auth.login');
Route::post('/login', [LoginController::class, 'login'])->name('auth.login.post')->middleware('throttle:5,1');

// Logout routes (POST es el estándar; GET redirige al mismo método para soportar links directos)
Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout')->withoutMiddleware('guest')->middleware('auth');
Route::get('/logout', [LoginController::class, 'logout'])->withoutMiddleware('guest')->middleware('auth');

// Password reset routes
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequest'])->name('auth.password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('auth.password.email')->middleware('throttle:password-reset');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('auth.password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('auth.password.update');

// Email verification routes
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('auth.verification.verify');
Route::get('/email/resend', [VerificationController::class, 'resend'])->name('auth.verification.resend');

// Validation route
Route::get('/validation', [ValidationController::class, 'show'])->name('auth.validation');

// Magic link login (passwordless)
Route::get('/magic-link', [MagicLinkController::class, 'show'])->name('auth.magic-link');
Route::post('/magic-link', [MagicLinkController::class, 'request'])->name('auth.magic-link.request')->middleware('throttle:5,15');
Route::get('/magic-link/consume/{token}', [MagicLinkController::class, 'consume'])->name('auth.magic-link.consume')->middleware('throttle:10,15');

// Email change confirmation (public, linked from email)
Route::get('/email/change/confirm/{token}', [PublicEmailChangeController::class, 'confirm'])
    ->name('auth.email-change.confirm')
    ->middleware('throttle:10,60');

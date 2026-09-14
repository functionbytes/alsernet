<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\AuthApiController;
use Modules\Auth\Http\Controllers\Api\TokenApiController;

/*
|--------------------------------------------------------------------------
| Auth API Routes
|--------------------------------------------------------------------------
|
| Prefix: api/auth (applied by ServiceProvider)
| Name: api.auth.* (applied by ServiceProvider)
|
*/

// Public
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])->name('login');
    Route::post('/two-factor/challenge', [AuthApiController::class, 'twoFactorChallenge'])->name('two-factor');
});

// Authenticated (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthApiController::class, 'me'])->name('me');
    Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');
    Route::post('/logout-all', [AuthApiController::class, 'logoutAll'])->name('logout-all');

    // Personal access token management
    Route::get('/tokens', [TokenApiController::class, 'index'])->name('tokens.index');
    Route::post('/tokens', [TokenApiController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{id}', [TokenApiController::class, 'destroy'])->name('tokens.destroy');
});

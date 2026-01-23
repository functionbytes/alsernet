<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Api\PublicReturnController;
use Modules\Returns\Http\Controllers\Api\ReturnController;

/*
|--------------------------------------------------------------------------
| Return API Routes
|--------------------------------------------------------------------------
|
| Public and authenticated API routes for return requests management
|
*/

// Public API routes (no authentication required)
Route::prefix('public')->group(function () {
    // Check return status using order ID and email
    Route::post('/status', [PublicReturnController::class, 'getStatus']);

    // Create guest return request
    Route::post('/create', [PublicReturnController::class, 'createGuestReturn']);
});

// Authenticated API routes
Route::middleware('auth:sanctum')->group(function () {
    // List customer's returns
    Route::get('/', [ReturnController::class, 'index']);

    // Create a new return request
    Route::post('/', [ReturnController::class, 'store']);

    // Get form data for creating a return
    Route::get('/form-data', [ReturnController::class, 'getFormData']);

    // Get specific return details
    Route::get('/{id}', [ReturnController::class, 'show']);

    // Download return PDF
    Route::get('/{id}/pdf', [ReturnController::class, 'downloadPDF']);
});

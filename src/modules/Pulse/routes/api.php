<?php

use Illuminate\Support\Facades\Route;
use Modules\Pulse\Http\Controllers\PulseController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('pulses', PulseController::class)->names('pulse');
});

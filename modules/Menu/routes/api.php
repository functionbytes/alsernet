<?php

use Illuminate\Support\Facades\Route;
use modules\Menu\Http\Controllers\MenuController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('menus', MenuController::class)->names('menu');
});

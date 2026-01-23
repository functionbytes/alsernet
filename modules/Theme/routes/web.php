<?php

use Illuminate\Support\Facades\Route;
use Modules\Theme\Http\Controllers\AssetController;

/**
 * Theme Asset Routes
 *
 * Serves theme assets directly from modules/Theme/public/theme/
 * without requiring them to be published to public/
 */

Route::get('/theme-asset/{path}', [AssetController::class, 'asset'])
    ->where('path', '.*')
    ->name('theme.asset')
    ->middleware('web');

<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\Http\Controllers\SeoMetaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Seo module.
|
*/

Route::prefix('api/seo-helper')->middleware('api')->group(function () {
    // SEO Meta CRUD
    Route::apiResource('seo-metas', SeoMetaController::class);

    // Additional endpoints
    Route::post('seo-metas/preview', [SeoMetaController::class, 'preview'])->name('seo-metas.preview');
    Route::post('seo-metas/bulk-update', [SeoMetaController::class, 'bulkUpdate'])->name('seo-metas.bulk-update');
    Route::get('seo-metas/statistics/all', [SeoMetaController::class, 'statistics'])->name('seo-metas.statistics');
});

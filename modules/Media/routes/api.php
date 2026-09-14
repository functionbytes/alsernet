<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\Api\MediaFileApiController;
use Modules\Media\Http\Controllers\Api\MediaFolderApiController;
use Modules\Media\Http\Controllers\Api\MediaHealthController;

Route::middleware(['api', 'throttle:30,1'])
    ->get('media/health', [MediaHealthController::class, 'check'])
    ->name('api.media.health');

Route::middleware(['api', 'auth:sanctum', 'throttle:120,1'])
    ->prefix('media')
    ->name('api.media.')
    ->group(function (): void {

        Route::prefix('files')->name('files.')->group(function (): void {
            Route::get('/', [MediaFileApiController::class, 'index'])->name('index');
            Route::get('/search', [MediaFileApiController::class, 'search'])->name('search');
            Route::post('/', [MediaFileApiController::class, 'store'])->middleware('throttle:30,1')->name('store');
            Route::post('/upload-from-url', [MediaFileApiController::class, 'uploadFromUrl'])->middleware('throttle:20,1')->name('upload-from-url');
            Route::get('/{file}', [MediaFileApiController::class, 'show'])->name('show');
            Route::put('/{file}', [MediaFileApiController::class, 'update'])->name('update');
            Route::delete('/{file}', [MediaFileApiController::class, 'destroy'])->name('destroy');
            Route::post('/{file}/move', [MediaFileApiController::class, 'move'])->name('move');
            Route::post('/{file}/copy', [MediaFileApiController::class, 'copy'])->name('copy');
            Route::post('/{file}/restore', [MediaFileApiController::class, 'restore'])->name('restore')->withTrashed();
            Route::post('/{file}/share', [MediaFileApiController::class, 'createShareLink'])->name('share');
            Route::get('/{file}/similar', [MediaFileApiController::class, 'similar'])->name('similar');
        });

        Route::prefix('folders')->name('folders.')->group(function (): void {
            Route::get('/', [MediaFolderApiController::class, 'index'])->name('index');
            Route::post('/', [MediaFolderApiController::class, 'store'])->name('store');
            Route::get('/{folder}', [MediaFolderApiController::class, 'show'])->name('show');
            Route::put('/{folder}', [MediaFolderApiController::class, 'update'])->name('update');
            Route::delete('/{folder}', [MediaFolderApiController::class, 'destroy'])->name('destroy');
            Route::post('/{folder}/move', [MediaFolderApiController::class, 'move'])->name('move');
            Route::post('/{folder}/restore', [MediaFolderApiController::class, 'restore'])->name('restore')->withTrashed();
        });
    });

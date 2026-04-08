<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;
use Modules\Media\Http\Controllers\MediaFileController;
use Modules\Media\Http\Controllers\MediaFolderController;
use Modules\Media\Http\Controllers\PublicMediaController;

// Public indirect URL (throttled, no auth required)
Route::middleware(['web', 'throttle:60,1'])
    ->get('media/files/{hash}/{id}', [PublicMediaController::class, 'show'])
    ->name('media.indirect.url');

// Media manager - authenticated
Route::middleware(['web', 'auth'])->prefix('panel/media')->name('media.')->group(function (): void {
    // Main view & listing
    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::get('/list', [MediaController::class, 'getList'])->name('list');
    Route::get('/breadcrumbs', [MediaController::class, 'getBreadcrumbs'])->name('breadcrumbs');
    Route::post('/set-disk', [MediaController::class, 'setActiveDisk'])->name('set-disk');
    Route::delete('/trash/empty', [MediaController::class, 'emptyTrash'])->name('trash.empty');

    // File operations
    Route::prefix('files')->name('files.')->group(function (): void {
        Route::post('/upload', [MediaFileController::class, 'upload'])->name('upload');
        Route::post('/upload-url', [MediaFileController::class, 'uploadFromUrl'])->name('upload-url');
        Route::put('/{file}/rename', [MediaFileController::class, 'rename'])->name('rename');
        Route::post('/{file}/copy', [MediaFileController::class, 'copy'])->name('copy');
        Route::delete('/{file}', [MediaFileController::class, 'delete'])->name('delete');
        Route::post('/{file}/restore', [MediaFileController::class, 'restore'])->name('restore');
        Route::put('/{file}/move', [MediaFileController::class, 'move'])->name('move');
        Route::post('/{file}/toggle-favorite', [MediaFileController::class, 'toggleFavorite'])->name('toggle-favorite');
    });

    // Folder operations
    Route::prefix('folders')->name('folders.')->group(function (): void {
        Route::post('/create', [MediaFolderController::class, 'store'])->name('create');
        Route::put('/{folder}/rename', [MediaFolderController::class, 'rename'])->name('rename');
        Route::delete('/{folder}', [MediaFolderController::class, 'delete'])->name('delete');
        Route::post('/{folder}/restore', [MediaFolderController::class, 'restore'])->name('restore');
        Route::put('/{folder}/move', [MediaFolderController::class, 'move'])->name('move');
    });
});

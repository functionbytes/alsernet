<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaManagerController;

// Public media page - accessible without authentication
Route::middleware(['web'])->prefix('media')->name('media.')->group(function () {
    Route::get('/', [MediaManagerController::class, 'index'])->name('index');
    Route::get('/list', [MediaManagerController::class, 'getList'])->name('list');
});

// Protected media operations - require authentication
Route::middleware(['web', 'auth'])->prefix('media')->name('media.')->group(function () {
    Route::post('/upload', [MediaManagerController::class, 'uploadFile'])->name('upload');
    Route::post('/upload-url', [MediaManagerController::class, 'uploadFromUrl'])->name('upload-url');
    Route::post('/folder/create', [MediaManagerController::class, 'createFolder'])->name('folder.create');
    Route::put('/file/{file}/rename', [MediaManagerController::class, 'renameFile'])->name('file.rename');
    Route::put('/folder/{folder}/rename', [MediaManagerController::class, 'renameFolder'])->name('folder.rename');
    Route::post('/file/{file}/copy', [MediaManagerController::class, 'copyFile'])->name('file.copy');
    Route::delete('/file/{file}', [MediaManagerController::class, 'deleteFile'])->name('file.delete');
    Route::delete('/folder/{folder}', [MediaManagerController::class, 'deleteFolder'])->name('folder.delete');
    Route::post('/file/{file}/restore', [MediaManagerController::class, 'restoreFile'])->name('file.restore');
    Route::post('/folder/{folder}/restore', [MediaManagerController::class, 'restoreFolder'])->name('folder.restore');
    Route::put('/file/{file}/move', [MediaManagerController::class, 'moveFile'])->name('file.move');
    Route::put('/folder/{folder}/move', [MediaManagerController::class, 'moveFolder'])->name('folder.move');
    Route::post('/file/{file}/toggle-favorite', [MediaManagerController::class, 'toggleFavorite'])->name('file.toggle-favorite');
    Route::delete('/trash/empty', [MediaManagerController::class, 'emptyTrash'])->name('trash.empty');
    Route::post('/set-disk', [MediaManagerController::class, 'setActiveDisk'])->name('set-disk');
});

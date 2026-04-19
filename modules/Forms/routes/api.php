<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\Api\FormApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('forms')
    ->name('api.forms.')
    ->group(function () {
        Route::get('/{form}/submissions', [FormApiController::class, 'index'])->name('submissions.index');
        Route::get('/{form}/submissions/{submission}', [FormApiController::class, 'show'])->name('submissions.show');
        Route::delete('/{form}/submissions/{submission}', [FormApiController::class, 'destroy'])->name('submissions.destroy');
        Route::get('/{form}/stats', [FormApiController::class, 'stats'])->name('stats');
        Route::get('/{form}/preview-html', [FormApiController::class, 'previewHtml'])
            ->middleware('throttle:60,1')
            ->name('preview-html');
    });

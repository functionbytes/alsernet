<?php

use Illuminate\Support\Facades\Route;
use Modules\PriceLabels\Http\Controllers\Admin\PriceLabelEditorController;
use Modules\PriceLabels\Http\Controllers\Admin\PriceLabelFieldController;
use Modules\PriceLabels\Http\Controllers\Admin\PriceLabelGenerationController;
use Modules\PriceLabels\Http\Controllers\Admin\PriceLabelTemplateController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/pricelabels')
    ->name('pricelabels.')
    ->group(function () {
        Route::get('/', [PriceLabelTemplateController::class, 'index'])->name('index');
        Route::get('/create', [PriceLabelTemplateController::class, 'create'])->name('create');
        Route::post('/', [PriceLabelTemplateController::class, 'store'])->name('store');

        Route::get('/history', [PriceLabelGenerationController::class, 'index'])->name('history.index');
        Route::get('/history/{generation}/download', [PriceLabelGenerationController::class, 'download'])->name('history.download');
        Route::delete('/history/{generation}', [PriceLabelGenerationController::class, 'destroy'])->name('history.destroy');
        Route::post('/history/bulk-action', [PriceLabelGenerationController::class, 'bulkAction'])->name('history.bulk-action');

        Route::get('/{price_label_template}/edit', [PriceLabelTemplateController::class, 'edit'])->name('edit');
        Route::put('/{price_label_template}', [PriceLabelTemplateController::class, 'update'])->name('update');
        Route::delete('/{price_label_template}', [PriceLabelTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{price_label_template}/duplicate', [PriceLabelTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/bulk-action', [PriceLabelTemplateController::class, 'bulkAction'])->name('bulk-action');

        Route::post('/{price_label_template}/fields', [PriceLabelFieldController::class, 'store'])->name('fields.store');
        Route::delete('/{price_label_template}/fields/{key}', [PriceLabelFieldController::class, 'destroy'])->name('fields.destroy');

        Route::post('/{price_label_template}/positions', [PriceLabelEditorController::class, 'savePositions'])->name('positions.save');
        Route::post('/{price_label_template}/generate', [PriceLabelEditorController::class, 'generate'])->name('generate');
        Route::post('/{price_label_template}/preview-excel', [PriceLabelEditorController::class, 'previewExcel'])->name('preview-excel');
    });

<?php

use Illuminate\Support\Facades\Route;
use Modules\Template\Http\Controllers\TemplateController;
use Modules\Template\Http\Controllers\TemplateWebController;
use Modules\Template\Http\Controllers\ThemeCustomHtmlController;
use Modules\Template\Http\Controllers\ThemeCustomJsController;
use Modules\Template\Http\Controllers\UiBlockController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Admin Routes - Theme Settings
Route::prefix('settings/theme')
    ->middleware(['web', 'auth'])
    ->name('settings.theme.')
    ->group(function () {
        // Custom HTML Settings
        Route::get('/custom/html', [ThemeCustomHtmlController::class, 'index'])
            ->name('custom-html');

        Route::post('/custom/html', [ThemeCustomHtmlController::class, 'update'])
            ->name('custom-html.update');

        // Custom JS Settings
        Route::get('/custom/js', [ThemeCustomJsController::class, 'index'])
            ->name('custom-js');

        Route::post('/custom/js', [ThemeCustomJsController::class, 'update'])
            ->name('custom-js.update');
    });

// Admin Routes - Template Management
Route::prefix('settings/templates')
    ->middleware(['web', 'auth'])
    ->name('settings.templates.')
    ->group(function () {
        // Custom CSS Management - MUST be before /{template} routes
        Route::get('/custom/css', [\Modules\Template\Http\Controllers\CustomCssController::class, 'edit'])
            ->name('custom-css.edit');
        Route::post('/custom/css', [\Modules\Template\Http\Controllers\CustomCssController::class, 'update'])
            ->name('custom-css.update');

        // Index - Grid de templates
        Route::get('', [TemplateController::class, 'index'])
            ->name('index');

        // Create - Formulario nuevo
        Route::get('/create', [TemplateController::class, 'create'])
            ->name('create');

        // Store - Guardar nuevo
        Route::post('', [TemplateController::class, 'store'])
            ->name('store');

        // Show - Detalles
        Route::get('/{template}', [TemplateController::class, 'show'])
            ->name('show');

        // Edit - Formulario editar
        Route::get('/{template}/edit', [TemplateController::class, 'edit'])
            ->name('edit');

        // Update - Guardar cambios
        Route::put('/{template}', [TemplateController::class, 'update'])
            ->name('update');

        // Delete - Eliminar
        Route::delete('/{template}', [TemplateController::class, 'destroy'])
            ->name('destroy');

        // Import - Importar desde ZIP
        Route::post('/import', [TemplateController::class, 'importZip'])
            ->name('import');

        // AJAX Actions - Activar template
        Route::post('/activate', [TemplateController::class, 'postActivateTemplate'])
            ->name('activate');

        // AJAX Actions - Eliminar template
        Route::post('/remove', [TemplateController::class, 'postRemoveTemplate'])
            ->name('remove');

        // Versioning - Historial de versiones
        Route::prefix('/{template}/versions')
            ->name('versions.')
            ->group(function () {
                Route::get('', [TemplateController::class, 'versionsIndex'])
                    ->name('index');

                Route::get('/{version}', [TemplateController::class, 'showVersion'])
                    ->name('show');

                Route::post('/{version}/restore', [TemplateController::class, 'restoreVersion'])
                    ->name('restore');

                Route::get('/{version}/compare/{compareWith}', [TemplateController::class, 'compareVersions'])
                    ->name('compare');
            });
    });

// Admin Routes - UI Blocks Management
Route::prefix('settings/ui-blocks')
    ->middleware(['web', 'auth'])
    ->name('settings.ui-blocks.')
    ->group(function () {
        Route::get('', [UiBlockController::class, 'index'])->name('index');
        Route::get('/create', [UiBlockController::class, 'create'])->name('create');
        Route::post('', [UiBlockController::class, 'store'])->name('store');
        Route::get('/{uiBlock}/edit', [UiBlockController::class, 'edit'])->name('edit');
        Route::put('/{uiBlock}', [UiBlockController::class, 'update'])->name('update');
        Route::delete('/{uiBlock}', [UiBlockController::class, 'destroy'])->name('destroy');
        Route::post('/order', [UiBlockController::class, 'updateOrder'])->name('order');
    });

// API - UI Blocks para Page editor
Route::get('/api/ui-blocks', [UiBlockController::class, 'apiIndex'])
    ->middleware(['web', 'auth'])
    ->name('api.ui-blocks');

// Public Routes - Frontend Template Rendering
Route::get('/template/{slug}', [TemplateWebController::class, 'render'])
    ->name('templates.public.render');

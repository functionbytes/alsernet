<?php

use Illuminate\Support\Facades\Route;
use Modules\Template\Http\Controllers\TemplateController;
use Modules\Template\Http\Controllers\TemplateWebController;

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

// Admin Routes - Template Management
Route::prefix('settings/templates')
    ->middleware(['web', 'auth'])
    ->name('settings.templates.')
    ->group(function () {
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
            ->name('activate')
            ->middleware('preventDemo');

        // AJAX Actions - Eliminar template
        Route::post('/remove', [TemplateController::class, 'postRemoveTemplate'])
            ->name('remove')
            ->middleware('preventDemo');

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

// Public Routes - Frontend Template Rendering
Route::get('/template/{slug}', [TemplateWebController::class, 'render'])
    ->name('templates.public.render');

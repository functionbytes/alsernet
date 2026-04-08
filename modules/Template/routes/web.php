<?php

use Illuminate\Support\Facades\Route;
use Modules\Template\Http\Controllers\CustomCssController;
use Modules\Template\Http\Controllers\MenuController;
use Modules\Template\Http\Controllers\ShortcodeCategoryController;
use Modules\Template\Http\Controllers\ShortcodeController;
use Modules\Template\Http\Controllers\TemplateController;
use Modules\Template\Http\Controllers\TemplateWebController;
use Modules\Template\Http\Controllers\ThemeCustomHtmlController;
use Modules\Template\Http\Controllers\ThemeCustomJsController;
use Modules\Template\Http\Controllers\ThemeOptionController;

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
Route::prefix('panel/settings/theme')
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

        // Theme Options
        Route::get('/options', [ThemeOptionController::class, 'index'])
            ->name('options');

        Route::post('/options', [ThemeOptionController::class, 'update'])
            ->name('options.update');
    });

// Admin Routes - Template Management
Route::prefix('panel/settings/templates')
    ->middleware(['web', 'auth'])
    ->name('settings.templates.')
    ->group(function () {
        // Custom CSS Management - MUST be before /{template} routes
        Route::get('/custom/css', [CustomCssController::class, 'edit'])
            ->name('custom-css.edit');
        Route::post('/custom/css', [CustomCssController::class, 'update'])
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

        // Vista previa AJAX
        Route::post('/preview', [TemplateController::class, 'preview'])->name('preview');

        // Import - Página dedicada de importación
        Route::get('/import', [TemplateController::class, 'importPage'])
            ->name('import.page');

        // Import - Importar desde ZIP
        Route::post('/import', [TemplateController::class, 'importZip'])
            ->name('import');

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

// Admin Routes - Shortcode Categories Management
Route::prefix('panel/settings/shortcode-categories')
    ->middleware(['web', 'auth'])
    ->name('settings.shortcode-categories.')
    ->group(function () {
        Route::get('', [ShortcodeCategoryController::class, 'index'])->name('index');
        Route::get('/create', [ShortcodeCategoryController::class, 'create'])->name('create');
        Route::post('', [ShortcodeCategoryController::class, 'store'])->name('store');
        Route::get('/{shortcodeCategory}/edit', [ShortcodeCategoryController::class, 'edit'])->name('edit');
        Route::put('/{shortcodeCategory}', [ShortcodeCategoryController::class, 'update'])->name('update');
        Route::delete('/{shortcodeCategory}', [ShortcodeCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{shortcodeCategory}/toggle', [ShortcodeCategoryController::class, 'toggle'])->name('toggle');
        Route::post('/order', [ShortcodeCategoryController::class, 'updateOrder'])->name('order');
        Route::post('/bulk-action', [ShortcodeCategoryController::class, 'bulkAction'])->name('bulk-action');
    });

// Admin Routes - Shortcodes Management
Route::prefix('panel/settings/shortcodes')
    ->middleware(['web', 'auth'])
    ->name('settings.shortcodes.')
    ->group(function () {
        Route::get('', [ShortcodeController::class, 'index'])->name('index');
        Route::get('/create', [ShortcodeController::class, 'create'])->name('create');
        Route::post('', [ShortcodeController::class, 'store'])->name('store');
        Route::get('/{shortcode}/edit', [ShortcodeController::class, 'edit'])->name('edit');
        Route::put('/{shortcode}', [ShortcodeController::class, 'update'])->name('update');
        Route::delete('/{shortcode}', [ShortcodeController::class, 'destroy'])->name('destroy');
        Route::post('/{shortcode}/toggle', [ShortcodeController::class, 'toggle'])->name('toggle');
        Route::post('/order', [ShortcodeController::class, 'updateOrder'])->name('order');
        Route::post('/bulk-action', [ShortcodeController::class, 'bulkAction'])->name('bulk-action');
    });

// Admin Routes - Menu Management
Route::prefix('panel/settings/menus')
    ->middleware(['web', 'auth'])
    ->name('settings.menus.')
    ->group(function () {
        Route::get('', [MenuController::class, 'index'])->name('index');
        Route::get('/create', [MenuController::class, 'create'])->name('create');
        Route::post('', [MenuController::class, 'store'])->name('store');
        Route::post('/bulk-action', [MenuController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
        Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');

        // Resolve node URL and render HTML partial
        Route::post('/{menu}/node', [MenuController::class, 'getNode'])->name('get-node');

        // Menu structure update (drag & drop)
        Route::post('/{menu}/structure', [MenuController::class, 'updateStructure'])->name('structure.update');

        // Menu items
        Route::post('/{menu}/items', [MenuController::class, 'storeItem'])->name('items.store');
        Route::put('/{menu}/items/{item}', [MenuController::class, 'updateItem'])->name('items.update');
        Route::delete('/{menu}/items/{item}', [MenuController::class, 'destroyItem'])->name('items.destroy');
    });

// API - Shortcodes para Page editor (base de datos)
Route::get('/api/page-shortcodes', [ShortcodeController::class, 'apiIndex'])
    ->middleware(['web', 'auth', 'throttle:60,1', 'can:viewAny,Modules\Template\Models\Template'])
    ->name('api.page-shortcodes');

// API - Shortcodes registrados en runtime (compilador)
Route::get('/api/runtime-shortcodes', [ShortcodeController::class, 'apiRuntimeIndex'])
    ->middleware(['web', 'auth', 'throttle:60,1', 'can:viewAny,Modules\Template\Models\Template'])
    ->name('api.runtime-shortcodes');

// Public Routes - Frontend Template Rendering
Route::get('/template/{slug}', [TemplateWebController::class, 'render'])
    ->name('templates.public.render');

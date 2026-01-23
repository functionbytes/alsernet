<?php

use Illuminate\Support\Facades\Route;
use Modules\Document\Http\Controllers\DocumentConfigurationController;
use Modules\Document\Http\Controllers\DocumentGroupsController;
use Modules\Document\Http\Controllers\DocumentProductBlockadeController;
use Modules\Document\Http\Controllers\DocumentsController;
use Modules\Document\Http\Controllers\DocumentSlaPoliciesController;
use Modules\Document\Http\Controllers\DocumentSyncController;
use Modules\Document\Http\Controllers\DocumentTypeController;
use Modules\Document\Http\Controllers\DocumentValidationConditionController;
use Modules\Document\Http\Controllers\Settings\DocumentGroupPermissionsController;
use Modules\Document\Http\Controllers\Settings\StageEmailActionController;

/*
|--------------------------------------------------------------------------
| Document Routes - Operational & Configuration
|--------------------------------------------------------------------------
|
| Rutas para la gestión operacional y configuración de documentos
| Operational routes use /documents prefix
| Configuration routes use /settings/documents prefix
| Middleware: web, auth, permission-based access
| Authorization: Handled in controllers via traits
|
*/

Route::middleware(['web', 'auth'])->group(function () {

    // ====================================================================
    // OPERATIONAL ROUTES - /documents
    // ====================================================================
    Route::prefix('documents')->name('documents.')->group(function () {
        // Listing routes
        Route::get('/', [DocumentsController::class, 'index'])->name('index');
        Route::get('/pending', [DocumentsController::class, 'pending'])->name('pending');

        // Import routes - Refactored to DocumentSyncController
        Route::get('/import', [DocumentSyncController::class, 'importIndex'])->name('import');
        Route::get('/import/api', [DocumentSyncController::class, 'importApi'])->name('import.api');
        Route::get('/import/erp', [DocumentSyncController::class, 'importErp'])->name('import.erp');

        // ERP sync routes
        Route::post('/sync/from-erp', [DocumentsController::class, 'syncFromErp'])->name('sync.from-erp');

        // Document views
        Route::get('/show/{uid}', [DocumentsController::class, 'show'])->name('show');
        Route::get('/summary/{uid}', [DocumentsController::class, 'summary'])->name('summary');
        Route::get('/manage/{uid}', [DocumentsController::class, 'manage'])->name('manage');
        Route::post('/manage/{uid}/update-configuration', [DocumentsController::class, 'updateConfiguration'])->name('update-configuration');
        Route::get('/emails/{uid}', [DocumentsController::class, 'emailHistory'])->name('emails');
        Route::get('/emails/preview/{mail}', [DocumentsController::class, 'emailPreview'])->name('emails.preview');

        Route::get('/validation/history/{uid}', [DocumentsController::class, 'validationHistory'])->name('validation-history');
        Route::get('/validation/history/stage/{stageNumber}', [DocumentsController::class, 'validationHistoryByStage'])->name('validation-history.stage');
    });

    // ====================================================================
    // CONFIGURATION ROUTES - /settings/documents (Admin only)
    // ====================================================================
    Route::prefix('settings/documents')->name('settings.documents.')
        ->middleware(['role:super-admin'])
        ->group(function () {

            Route::get('/', [DocumentConfigurationController::class, 'index'])->name('configurations');

            Route::prefix('configurations')->name('configurations.')->group(function () {
                // Views
                Route::get('/', [DocumentConfigurationController::class, 'globalSettings'])->name('global');
                Route::get('/search-templates', [DocumentConfigurationController::class, 'searchTemplates'])->name('search-templates');

                // Storage configuration routes
                Route::get('/storage', [DocumentConfigurationController::class, 'storageSettings'])->name('storage');
                Route::get('/storage/stats/{diskName}', [DocumentConfigurationController::class, 'getStorageStats'])->name('storage.stats');
                Route::get('/storage/history', [DocumentConfigurationController::class, 'getStorageConfigurationHistory'])->name('storage.history');
                Route::post('/storage/test', [DocumentConfigurationController::class, 'testStorageConnection'])->name('storage.test');
                Route::post('/storage/update', [DocumentConfigurationController::class, 'updateStorageSettings'])->name('storage.update');

                // Email and SLA settings
                Route::get('/sla', [DocumentConfigurationController::class, 'slaSettings'])->name('sla');

                // Update operations
                Route::patch('/', [DocumentConfigurationController::class, 'updateGlobalSettings'])->name('update');
                Route::post('/', [DocumentConfigurationController::class, 'updateGlobalSettings'])->name('store');
                Route::post('/sla/update', [DocumentConfigurationController::class, 'updateSlaSettings'])->name('sla.update');
                Route::post('/reset-defaults', [DocumentConfigurationController::class, 'resetToDefaults'])->name('reset-defaults');
            });

            Route::prefix('types')->name('types.')->group(function () {
                // Views
                Route::get('/', [DocumentTypeController::class, 'index'])->name('index');
                Route::get('/create', [DocumentTypeController::class, 'create'])->name('create');
                Route::get('/edit/{documentType}', [DocumentTypeController::class, 'edit'])->name('edit');
                Route::get('/export/all', [DocumentTypeController::class, 'export'])->name('export');

                // Actions
                Route::post('/', [DocumentTypeController::class, 'store'])->name('store');
                Route::patch('/{documentType}', [DocumentTypeController::class, 'update'])->name('update');
                Route::delete('/{documentType}', [DocumentTypeController::class, 'destroy'])->name('destroy');
                Route::post('/{documentType}/toggle-active', [DocumentTypeController::class, 'toggleActive'])->name('toggle-active');
            });

            // Validation Conditions
            Route::prefix('conditions')->name('conditions.')->group(function () {
                // Views
                Route::get('/', [DocumentValidationConditionController::class, 'index'])->name('index');
                Route::get('/create', [DocumentValidationConditionController::class, 'create'])->name('create');
                Route::get('/edit/{condition}', [DocumentValidationConditionController::class, 'edit'])->name('edit');

                // Actions
                Route::post('/', [DocumentValidationConditionController::class, 'store'])->name('store');
                Route::patch('/{condition}', [DocumentValidationConditionController::class, 'update'])->name('update');
                Route::delete('/{condition}', [DocumentValidationConditionController::class, 'destroy'])->name('destroy');
                Route::post('/{condition}/toggle-active', [DocumentValidationConditionController::class, 'toggleActive'])->name('toggle-active');
            });

            // SLA Policies
            Route::prefix('sla-policies')->name('sla-policies.')->group(function () {
                // Views
                Route::get('/', [DocumentSlaPoliciesController::class, 'index'])->name('index');
                Route::get('create', [DocumentSlaPoliciesController::class, 'create'])->name('create');
                Route::get('{policy}', [DocumentSlaPoliciesController::class, 'show'])->name('show');
                Route::get('/edit/{policy}', [DocumentSlaPoliciesController::class, 'edit'])->name('edit');

                // Actions
                Route::post('/', [DocumentSlaPoliciesController::class, 'store'])->name('store');
                Route::patch('/{policy}', [DocumentSlaPoliciesController::class, 'update'])->name('update');
                Route::delete('/{policy}', [DocumentSlaPoliciesController::class, 'destroy'])->name('destroy');
                Route::post('/{policy}/toggle', [DocumentSlaPoliciesController::class, 'toggle'])->name('toggle');
            });

            // Document Groups (Validator Groups)
            Route::prefix('groups')->name('groups.')->group(function () {
                // Views
                Route::get('/', [DocumentGroupsController::class, 'index'])->name('index');
                Route::get('create', [DocumentGroupsController::class, 'create'])->name('create');
                Route::get('{group}/edit', [DocumentGroupsController::class, 'edit'])->name('edit');
                Route::get('{group}/configuration', [DocumentGroupsController::class, 'configuration'])->name('configuration');

                // Actions
                Route::post('/', [DocumentGroupsController::class, 'store'])->name('store');
                Route::patch('/{group}', [DocumentGroupsController::class, 'update'])->name('update');
                Route::delete('/{group}', [DocumentGroupsController::class, 'destroy'])->name('destroy');
                Route::post('/{group}/toggle', [DocumentGroupsController::class, 'toggle'])->name('toggle');
                Route::post('/{group}/configuration', [DocumentGroupsController::class, 'updateConfiguration'])->name('update-configuration');

                // Permissions management routes
                Route::prefix('permissions')->name('permissions.')->group(function () {
                    Route::get('/{group}/edit', [DocumentGroupPermissionsController::class, 'edit'])->name('edit');
                    Route::post('/{group}', [DocumentGroupPermissionsController::class, 'update'])->name('update');
                    Route::get('/{group}', [DocumentGroupPermissionsController::class, 'show'])->name('show');
                    Route::get('/available', [DocumentGroupPermissionsController::class, 'available'])->name('available');
                    Route::post('/bulk-update', [DocumentGroupPermissionsController::class, 'bulkUpdate'])->name('bulk-update');
                    Route::post('/{sourceGroup}/clone', [DocumentGroupPermissionsController::class, 'clone'])->name('clone');
                });
            });

            // Product Blockades
            Route::prefix('blockades')->name('blockades.')->group(function () {
                // Views
                Route::get('/', [DocumentProductBlockadeController::class, 'index'])->name('index');
                Route::get('/status', [DocumentProductBlockadeController::class, 'status'])->name('status');

                // Actions
                Route::post('/sync', [DocumentProductBlockadeController::class, 'sync'])->name('sync');
                Route::post('/labels/add', [DocumentProductBlockadeController::class, 'addLabel'])->name('labels.add');
                Route::post('/labels/delete', [DocumentProductBlockadeController::class, 'deleteLabel'])->name('labels.delete');
                Route::delete('/{id}', [DocumentProductBlockadeController::class, 'destroy'])->name('destroy');
            });

            // Stage email action routes
            Route::prefix('stage-email-actions')->name('stage-email-actions.')->group(function () {
                // Views
                Route::get('/', [StageEmailActionController::class, 'index'])->name('index');
                Route::get('/{stage}/edit', [StageEmailActionController::class, 'edit'])->name('edit');

                // Actions
                Route::post('/{stage}/update', [StageEmailActionController::class, 'update'])->name('update');
                Route::post('/store', [StageEmailActionController::class, 'store'])->name('store');
                Route::delete('/{id}', [StageEmailActionController::class, 'destroy'])->name('destroy');
            });

        });

});

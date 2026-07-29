<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\Settings\Categories\CategoriesController;
use Modules\Supplier\Http\Controllers\Settings\Monitoring\AiCostMonitoringController;
use Modules\Supplier\Http\Controllers\Settings\PromptTemplatesController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation\SupplierAutomationExecutionController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation\SupplierAutomationLogController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation\SupplierAutomationWorkflowController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierAiConfigController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierAutomationController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierCategoriesController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierContentController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierEndpointsController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierImportController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierProductChatController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierProductsController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierPromptsController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SuppliersController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierSourcesController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierExcludedGroupsController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierSyncConfigController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierSyncController;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierSyncFailuresController;

/*
|--------------------------------------------------------------------------
| Supplier Routes - Operational & Configuration
|--------------------------------------------------------------------------
|
| Rutas para la gestión operacional y configuración de proveedores.
| Operational routes use /suppliers prefix.
| Configuration routes use /setting/suppliers prefix.
| Authorization: Granular per-section permissions (no blanket suppliers.configure).
|
*/

Route::middleware(['web', 'auth'])->group(function () {

    // ====================================================================
    // OPERATIONAL ROUTES - /suppliers
    // Reads → suppliers.view ; writes → suppliers.configure
    // ====================================================================
    Route::prefix('panel/suppliers')->name('suppliers.')->group(function () {
        Route::middleware(['can:suppliers.view'])->group(function () {
            Route::get('/', [SuppliersController::class, 'index'])->name('index');
            Route::get('/show/{uid}', [SuppliersController::class, 'show'])->name('show');
            Route::get('/edit/{uid}', [SuppliersController::class, 'edit'])->name('edit');
        });

        Route::middleware(['can:suppliers.configure'])->group(function () {
            Route::put('/{uid}', [SuppliersController::class, 'update'])->name('update');
            Route::post('/{uid}/toggle', [SuppliersController::class, 'toggle'])->name('toggle');
            Route::post('/test-all', [SuppliersController::class, 'testAll'])->name('test-all');
        });

        Route::prefix('{supplierUid}/sources')->name('sources.')->middleware(['can:suppliers.sources.manage'])->group(function () {
            Route::get('/', [SupplierSourcesController::class, 'index'])->name('index');
            Route::get('/create', [SupplierSourcesController::class, 'create'])->name('create');
            Route::post('/', [SupplierSourcesController::class, 'store'])->name('store');
            Route::get('/{uid}/edit', [SupplierSourcesController::class, 'edit'])->name('edit');
            Route::put('/{uid}', [SupplierSourcesController::class, 'update'])->name('update');
            Route::delete('/{uid}', [SupplierSourcesController::class, 'destroy'])->name('destroy');
            Route::post('/{uid}/test', [SupplierSourcesController::class, 'testConnection'])->name('test');
        });

        Route::prefix('{supplierUid}/categories')->name('categories.')->middleware(['can:suppliers.categories.manage'])->group(function () {
            Route::get('/', [SupplierCategoriesController::class, 'index'])->name('index');
            Route::post('/', [SupplierCategoriesController::class, 'store'])->name('store');
            Route::put('/{categoryId}', [SupplierCategoriesController::class, 'update'])->name('update');
            Route::patch('/{categoryId}/toggle', [SupplierCategoriesController::class, 'toggle'])->name('toggle');
            Route::delete('/{categoryId}', [SupplierCategoriesController::class, 'destroy'])->name('destroy');
        });
    });

    // ====================================================================
    // CONFIGURATION ROUTES - /setting/suppliers
    // Each section carries its own permission middleware.
    // ====================================================================
    Route::prefix('panel/setting/suppliers')->name('settings.suppliers.')->group(function () {

        // ================================================================
        // IMPORT — suppliers.import
        // ================================================================
        Route::middleware(['can:suppliers.import'])->prefix('import')->name('import.')->group(function () {
            Route::get('/', [SupplierImportController::class, 'index'])->name('index');
            Route::get('/erp', [SupplierImportController::class, 'erp'])->name('erp');
            Route::post('/erp', [SupplierImportController::class, 'syncFromErp'])->name('sync-erp');
            Route::post('/sync', [SupplierImportController::class, 'sync'])->name('sync');
            Route::post('/erp/categories', [SupplierImportController::class, 'syncCategoriesFromErp'])->name('sync-categories');
            Route::post('/erp/models', [SupplierImportController::class, 'syncModelsFromErp'])->name('sync-models');
            Route::post('/erp/products', [SupplierImportController::class, 'syncProviderProductsFromErp'])->name('sync-products');
        });

        // ================================================================
        // SUPPLIER CRUD
        // Read (index, data, show) → suppliers.view
        // Detail page & data endpoints → suppliers.view.detail
        // Write (create, store, edit, update, destroy, toggle, bulk) → suppliers.configure
        // ================================================================
        Route::prefix('suppliers')->group(function () {

            Route::middleware(['can:suppliers.view'])->group(function () {
                Route::get('/', [SuppliersController::class, 'index'])->name('index');
                Route::get('/show/{uid}', [SuppliersController::class, 'show'])->name('show');
            });

            Route::middleware(['can:suppliers.view.detail'])->group(function () {
                Route::get('/detail/{uid}', [SuppliersController::class, 'detail'])->name('detail');
                Route::get('/products-data/{uid}', [SuppliersController::class, 'getProductsData'])->name('products-data');
                Route::get('/categories-data/{uid}', [SuppliersController::class, 'getCategoriesData'])->name('categories-data');
                Route::get('/prompts-data/{uid}', [SuppliersController::class, 'getPromptsData'])->name('prompts-data');
            });

            Route::middleware(['can:suppliers.configure'])->group(function () {
                Route::get('/create', [SuppliersController::class, 'create'])->name('create');
                Route::post('/', [SuppliersController::class, 'store'])->name('store');
                Route::post('/bulk-action', [SuppliersController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/edit/{uid}', [SuppliersController::class, 'edit'])->name('edit');
                Route::put('/update/{uid}', [SuppliersController::class, 'update'])->name('update');
                Route::delete('/destroy/{uid}', [SuppliersController::class, 'destroy'])->name('destroy');
                Route::post('/toggle/{uid}', [SuppliersController::class, 'toggle'])->name('toggle');
            });
        });

        // ================================================================
        // SOURCE DETECTION TOOL — suppliers.configure
        // ================================================================
        Route::middleware(['can:suppliers.configure'])->prefix('detect')->name('detect.')->group(function () {
            Route::get('/', [SupplierSourcesController::class, 'showDetectionTool'])->name('show');
            Route::post('/analyze', [SupplierSourcesController::class, 'detectSource'])->name('analyze');
        });

        // ================================================================
        // SOURCES per supplier — suppliers.sources.manage
        // ================================================================
        Route::middleware(['can:suppliers.sources.manage'])
            ->prefix('suppliers/sources/{supplierUid}')
            ->name('sources.')
            ->group(function () {
                Route::get('/', [SupplierSourcesController::class, 'index'])->name('index');
                Route::get('/create', [SupplierSourcesController::class, 'create'])->name('create');
                Route::post('/', [SupplierSourcesController::class, 'store'])->name('store');
                Route::post('/bulk-action', [SupplierSourcesController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/edit/{uid}', [SupplierSourcesController::class, 'edit'])->name('edit');
                Route::put('/update/{uid}', [SupplierSourcesController::class, 'update'])->name('update');
                Route::delete('/destroy/{uid}', [SupplierSourcesController::class, 'destroy'])->name('destroy');
                Route::post('/detect', [SupplierSourcesController::class, 'detectSource'])->name('detect');
                Route::post('/test/{uid}', [SupplierSourcesController::class, 'testConnection'])->name('test');
                Route::post('/extract/{uid}', [SupplierSourcesController::class, 'triggerExtraction'])->name('extract');
                Route::get('/status/{uid}', [SupplierSourcesController::class, 'getExtractionStatus'])->name('status');

                Route::prefix('files')->name('files.')->group(function () {
                    Route::post('/{uid}', [SupplierSourcesController::class, 'uploadFile'])->name('upload');
                    Route::delete('/{uid}/{fileUid}', [SupplierSourcesController::class, 'deleteFile'])->name('delete');
                    Route::get('/{uid}', [SupplierSourcesController::class, 'listFiles'])->name('list');
                });
            });

        // ================================================================
        // PROMPTS — suppliers.prompts.manage
        // ================================================================
        Route::middleware(['can:suppliers.prompts.manage'])->prefix('prompts')->name('prompts.')->group(function () {
            Route::get('/', [SupplierPromptsController::class, 'index'])->name('index');

            Route::get('/create', [SupplierPromptsController::class, 'create'])->name('create');
            Route::post('/bulk-action', [SupplierPromptsController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/check-subfamily-conflicts', [SupplierPromptsController::class, 'checkSubfamilyConflicts'])->name('check-subfamily-conflicts');
            Route::post('/', [SupplierPromptsController::class, 'store'])->name('store');
            Route::get('/{uid}', [SupplierPromptsController::class, 'show'])->name('show');
            Route::get('/{uid}/edit', [SupplierPromptsController::class, 'edit'])->name('edit');
            Route::put('/{uid}', [SupplierPromptsController::class, 'update'])->name('update');
            Route::delete('/{uid}', [SupplierPromptsController::class, 'destroy'])->name('destroy');
            Route::post('/{uid}/toggle', [SupplierPromptsController::class, 'toggle'])->name('toggle');
            Route::post('/{uid}/test', [SupplierPromptsController::class, 'test'])->name('test');
            Route::post('/{uid}/duplicate', [SupplierPromptsController::class, 'duplicate'])->name('duplicate');
            Route::get('/{uid}/preview', [SupplierPromptsController::class, 'preview'])->name('preview');
            Route::get('/{uid}/metrics', [SupplierPromptsController::class, 'getMetrics'])->name('metrics');
            Route::post('/{uid}/favorite', [SupplierPromptsController::class, 'toggleFavorite'])->name('favorite');
            Route::get('/{uid}/versions/{versionId}', [SupplierPromptsController::class, 'showVersion'])->name('version.show');
            Route::get('/builder/products/search', [SupplierPromptsController::class, 'searchProducts'])->name('builder.products.search');
            Route::get('/builder/products/{product}/sample', [SupplierPromptsController::class, 'sampleData'])->name('builder.products.sample');
        });

        // ================================================================
        // TEMPLATES — suppliers.templates.manage
        // ================================================================
        Route::middleware(['can:suppliers.templates.manage'])->prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [PromptTemplatesController::class, 'index'])->name('index');
            Route::get('/create', [PromptTemplatesController::class, 'create'])->name('create');
            Route::post('/', [PromptTemplatesController::class, 'store'])->name('store');
            Route::get('/{templateUid}/edit', [PromptTemplatesController::class, 'edit'])->name('edit');
            Route::put('/{templateUid}', [PromptTemplatesController::class, 'update'])->name('update');
            Route::delete('/{templateUid}', [PromptTemplatesController::class, 'destroy'])->name('destroy');
            Route::post('/{templateUid}/clone', [PromptTemplatesController::class, 'clone'])->name('clone');
            Route::post('/bulk-action', [PromptTemplatesController::class, 'bulkAction'])->name('bulk-action');
        });

        // ================================================================
        // AUTOMATION — suppliers.view.automation
        // ================================================================
        Route::middleware(['can:suppliers.view.automation'])->prefix('automation')->name('automation.')->group(function () {
            // Read-only
            Route::get('/', [SupplierAutomationController::class, 'index'])->name('index');
            Route::get('/stats', [SupplierAutomationController::class, 'getHealthMetrics'])->name('stats');
            Route::get('/logs', [SupplierAutomationLogController::class, 'index'])->name('logs');
            Route::prefix('logs/api')->name('logs.')->group(function () {
                Route::get('/', [SupplierAutomationLogController::class, 'data'])->name('data');
                Route::post('/download', [SupplierAutomationLogController::class, 'download'])->name('download');
            });
            Route::get('/executions/{uid}', [SupplierAutomationExecutionController::class, 'show'])->name('executions.show');

            // Write — requires automation.manage
            Route::middleware('can:suppliers.automation.manage')->group(function () {
                Route::post('/bulk-action', [SupplierAutomationController::class, 'bulkAction'])->name('bulk-action');

                Route::prefix('workflows')->name('workflows.')->group(function () {
                    Route::get('/create', [SupplierAutomationWorkflowController::class, 'create'])->name('create');
                    Route::post('/', [SupplierAutomationWorkflowController::class, 'store'])->name('store');
                    Route::get('/{uid}/edit', [SupplierAutomationWorkflowController::class, 'edit'])->name('edit');
                    Route::put('/{uid}', [SupplierAutomationWorkflowController::class, 'update'])->name('update');
                    Route::delete('/{uid}', [SupplierAutomationWorkflowController::class, 'destroy'])->name('destroy');
                    Route::post('/{uid}/run', [SupplierAutomationWorkflowController::class, 'run'])->name('run');
                    Route::post('/{uid}/toggle', [SupplierAutomationWorkflowController::class, 'toggle'])->name('toggle');
                    Route::post('/run-all', [SupplierAutomationWorkflowController::class, 'runAll'])->name('run-all');
                });

                Route::prefix('executions')->name('executions.')->group(function () {
                    Route::post('/{uid}/retry', [SupplierAutomationExecutionController::class, 'retry'])->name('retry');
                    Route::post('/{uid}/cancel', [SupplierAutomationExecutionController::class, 'cancel'])->name('cancel');
                    Route::post('/clear-failed', [SupplierAutomationExecutionController::class, 'clearFailed'])->name('clear-failed');
                });
            });
        });

        // ================================================================
        // CONTENT — suppliers.view.content
        // ================================================================
        Route::middleware(['can:suppliers.view.content'])->prefix('content')->name('content.')->group(function () {
            // Read-only
            Route::get('/', [SupplierContentController::class, 'index'])->name('index');
            Route::get('/coverage', [SupplierContentController::class, 'coverageReport'])->name('coverage');
            Route::get('/export', [SupplierContentController::class, 'export'])->name('export');
            Route::get('/stats', [SupplierContentController::class, 'getStats'])->name('stats');
            Route::get('/preview/{uid}', [SupplierContentController::class, 'preview'])->name('preview');
            Route::get('/show/{uid}', [SupplierContentController::class, 'show'])->name('show');
            Route::get('/assignable-users', [SupplierContentController::class, 'getAssignableUsers'])
                ->middleware('can:suppliers.content.assign-others')
                ->name('assignable-users');
            Route::get('/assignable', [SupplierContentController::class, 'assignable'])
                ->middleware('can:suppliers.content.assign-others')
                ->name('assignable');
            Route::get('/unassigned', [SupplierContentController::class, 'unassigned'])
                ->middleware('can:suppliers.content.assign-others')
                ->name('unassigned');
            Route::post('/assign-batch', [SupplierContentController::class, 'assignBatch'])
                ->middleware('can:suppliers.content.assign-others')
                ->name('assign-batch');

            // Write — requires content.manage
            Route::middleware('can:suppliers.content.manage')->group(function () {
                Route::post('/action/{uid}', [SupplierContentController::class, 'action'])->name('action');
                Route::post('/edit/{uid}', [SupplierContentController::class, 'edit'])->name('edit');
                Route::post('/publish/{uid}', [SupplierContentController::class, 'publish'])->name('publish');
                Route::post('/translate/{uid}', [SupplierContentController::class, 'translate'])->name('translate');
                Route::post('/bulk-action', [SupplierContentController::class, 'bulkAction'])->name('bulk-action');
                Route::post('/bulk-status', [SupplierContentController::class, 'bulkStatus'])->name('bulk-status');
                Route::post('/bulk-assign', [SupplierContentController::class, 'bulkAssign'])->name('bulk-assign');
                Route::post('/regenerate-by-quantity', [SupplierContentController::class, 'regenerateByQuantity'])
                    ->middleware('throttle:ai-bulk')
                    ->name('regenerate-by-quantity');
                Route::post('/{uid}/unassign', [SupplierContentController::class, 'unassign'])->name('unassign');
            });
        });

        // ================================================================
        // SUPPLIER CATEGORIES per supplier — suppliers.categories.manage
        // ================================================================
        Route::middleware(['can:suppliers.categories.manage'])
            ->prefix('{supplierUid}/categories')
            ->name('supplier-categories.')
            ->group(function () {
                Route::get('/', [SupplierCategoriesController::class, 'index'])->name('index');
                Route::post('/', [SupplierCategoriesController::class, 'store'])->name('store');
                Route::put('/{categoryId}', [SupplierCategoriesController::class, 'update'])->name('update');
                Route::patch('/{categoryId}/toggle', [SupplierCategoriesController::class, 'toggle'])->name('toggle');
                Route::delete('/{categoryId}', [SupplierCategoriesController::class, 'destroy'])->name('destroy');
            });

        // ================================================================
        // ERP CATEGORIES — suppliers.configure
        // ================================================================
        Route::middleware(['can:suppliers.configure'])->prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoriesController::class, 'index'])->name('index');
            Route::post('/bulk-action', [CategoriesController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/edit/{id}', [CategoriesController::class, 'edit'])->name('edit');
            Route::put('/edit/{id}', [CategoriesController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoriesController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/products', [CategoriesController::class, 'products'])->name('products');
            Route::post('/sync', [SupplierSyncController::class, 'syncCategoriesFromErp'])->name('sync');

            // Excel — importación y configuración
            Route::prefix('excel')->name('excel.')->group(function () {
                Route::get('/', [CategoriesController::class, 'importExcelPage'])->name('index');
                Route::post('/', [CategoriesController::class, 'importExcel'])->name('import');
                Route::post('/stream', [CategoriesController::class, 'importExcelStream'])->name('stream');
                Route::get('/config', [CategoriesController::class, 'excelConfig'])->name('config');
                Route::post('/config', [CategoriesController::class, 'saveExcelConfig'])->name('config.save');
            });
        });

        // ================================================================
        // ERP PRODUCTS — suppliers.view.products
        // ================================================================
        Route::middleware(['can:suppliers.view.products'])->prefix('products')->name('products.')->group(function () {
            // Read-only
            Route::get('/', [SupplierProductsController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [SupplierProductsController::class, 'edit'])->name('edit');
            Route::get('/{id}', [SupplierProductsController::class, 'show'])->name('show');
            Route::get('/{uid}/chat', [SupplierProductChatController::class, 'show'])->name('chat');
            Route::get('/{uid}/chat/{chatUid}/export', [SupplierProductChatController::class, 'export'])->name('chat.export');
            Route::middleware('throttle:ai-chat')->group(function () {
                Route::post('/{uid}/chat/send', [SupplierProductChatController::class, 'send'])->name('chat.send');
                Route::post('/{uid}/chat/regenerate', [SupplierProductChatController::class, 'regenerate'])->name('chat.regenerate');
                Route::post('/{uid}/chat/compare', [SupplierProductChatController::class, 'compare'])->name('chat.compare');
            });

            // Write — requires suppliers.configure
            Route::middleware('can:suppliers.configure')->group(function () {
                Route::put('/{id}', [SupplierProductsController::class, 'update'])->name('update');
                Route::post('/bulk-action', [SupplierProductsController::class, 'bulkAction'])->name('bulk-action');
                Route::post('/bulk-generate-content', [SupplierProductsController::class, 'bulkGenerateContent'])
                    ->middleware('throttle:ai-bulk')
                    ->name('bulk-generate-content');
                Route::post('/sync', [SupplierSyncController::class, 'syncProviderProductsFromErp'])->name('sync');
                Route::delete('/{uid}/chat/reset', [SupplierProductChatController::class, 'reset'])->name('chat.reset');
                Route::post('/{uid}/chat/save', [SupplierProductChatController::class, 'save'])->name('chat.save');
                Route::post('/{uid}/chat/{chatUid}/fork', [SupplierProductChatController::class, 'fork'])->name('chat.fork');
                Route::delete('/{uid}/chat/{chatUid}', [SupplierProductChatController::class, 'destroy'])->name('chat.destroy');
            });
        });

        // ================================================================
        // ENDPOINTS / INTEGRACIONES ERP — suppliers.sync.config
        // ================================================================
        Route::middleware(['can:suppliers.sync.config'])->prefix('endpoints')->name('endpoints.')->group(function () {
            Route::get('/', [SupplierEndpointsController::class, 'index'])->name('index');
            Route::patch('/', [SupplierEndpointsController::class, 'update'])->name('update');
            Route::post('/test', [SupplierEndpointsController::class, 'test'])->name('test');
        });

        // ================================================================
        // AI CONFIG — suppliers.sync.config
        // ================================================================
        Route::middleware(['can:suppliers.sync.config'])->prefix('ai-config')->name('ai-config.')->group(function () {
            Route::get('/', [SupplierAiConfigController::class, 'index'])->name('index');
            Route::patch('/', [SupplierAiConfigController::class, 'update'])->name('update');
            Route::post('/test', [SupplierAiConfigController::class, 'test'])->name('test');
        });

        // ================================================================
        // SYNC CONFIG — suppliers.sync.config
        // Must be defined before sync monitoring to avoid prefix conflict.
        // ================================================================
        Route::middleware(['can:suppliers.sync.config'])->prefix('sync/config')->name('sync.config.')->group(function () {
            Route::get('/', function () {
                return redirect()->route('settings.suppliers.sync.index');
            })->name('index');
            Route::get('/http', [SupplierSyncConfigController::class, 'httpConfig'])->name('http');
            Route::get('/data', [SupplierSyncConfigController::class, 'getData'])->name('data');
            Route::post('/', [SupplierSyncConfigController::class, 'store'])->name('store');
            Route::get('/status', [SupplierSyncConfigController::class, 'getStatus'])->name('status');
            Route::post('/bulk-toggle', [SupplierSyncConfigController::class, 'bulkToggle'])->name('bulk-toggle');
            Route::delete('/bulk-delete', [SupplierSyncConfigController::class, 'bulkDestroy'])->name('bulk-delete');
            Route::put('/{uid}', [SupplierSyncConfigController::class, 'update'])->name('update');
            Route::delete('/{uid}', [SupplierSyncConfigController::class, 'destroy'])->name('destroy');
            Route::post('/{uid}/trigger', [SupplierSyncConfigController::class, 'triggerNow'])->name('trigger');
            Route::post('/{uid}/toggle', [SupplierSyncConfigController::class, 'toggle'])->name('toggle');
            Route::post('/{uid}/reset-status', [SupplierSyncConfigController::class, 'resetStatus'])->name('reset-status');
        });

        // ================================================================
        // EXCLUDED ERP GROUPS — suppliers.sync.config
        // Must be defined before sync monitoring to avoid prefix conflict.
        // ================================================================
        Route::middleware(['can:suppliers.sync.config'])->prefix('sync/excluded-groups')->name('sync.excluded-groups.')->group(function () {
            Route::get('/', [SupplierExcludedGroupsController::class, 'index'])->name('index');
            Route::post('/', [SupplierExcludedGroupsController::class, 'store'])->name('store');
            Route::post('/bulk', [SupplierExcludedGroupsController::class, 'bulkStore'])->name('bulk');
            Route::put('/{id}', [SupplierExcludedGroupsController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [SupplierExcludedGroupsController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        // ================================================================
        // SYNC FAILURES — suppliers.sync.failures
        // Must be defined before sync monitoring to avoid prefix conflict.
        // ================================================================
        Route::middleware(['can:suppliers.sync.failures'])->prefix('sync/failures')->name('sync.failures.')->group(function () {
            Route::get('/', [SupplierSyncFailuresController::class, 'index'])->name('index');
            Route::post('/bulk-retry', [SupplierSyncFailuresController::class, 'bulkRetry'])->name('bulk-retry');
            Route::delete('/bulk-delete', [SupplierSyncFailuresController::class, 'bulkDestroy'])->name('bulk-delete');
            Route::get('/conflicts/{id}', [SupplierSyncFailuresController::class, 'showConflict'])->name('conflicts.show');
            Route::post('/{id}/retry', [SupplierSyncFailuresController::class, 'retry'])->whereNumber('id')->name('retry');
            Route::delete('/{id}', [SupplierSyncFailuresController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/{id}', [SupplierSyncFailuresController::class, 'show'])->whereNumber('id')->name('show');
        });

        // ================================================================
        // SYNC MONITORING — suppliers.sync.status
        // ================================================================
        Route::middleware(['can:suppliers.sync.status'])->prefix('sync')->name('sync.')->group(function () {
            Route::get('/', [SupplierSyncController::class, 'index'])->name('index');
            Route::get('/test', [SupplierSyncController::class, 'testIndex'])->name('test.index');
            Route::post('/test/run', [SupplierSyncController::class, 'testRun'])->name('test.run');
            Route::get('/cleanup', [SupplierSyncController::class, 'cleanupIndex'])->name('cleanup.index');
            Route::post('/cleanup', [SupplierSyncController::class, 'cleanupRun'])->name('cleanup.run');
            Route::get('/erp-model-count', [SupplierSyncController::class, 'erpModelCount'])->name('erp-model-count');
            Route::post('/sync-providers', [SupplierSyncController::class, 'syncProvidersFromErp'])->name('sync-providers');
            Route::post('/sync-products', [SupplierSyncController::class, 'syncProviderProductsFromErp'])->name('sync-products');
            Route::post('/sync-models', [SupplierSyncController::class, 'syncModelsFromErp'])->name('sync-models');
            Route::post('/settings', [SupplierSyncController::class, 'saveSettings'])->name('settings.save');
            Route::post('/bulk-retry', [SupplierSyncController::class, 'bulkRetry'])->name('bulk-retry');
            Route::post('/bulk-cancel', [SupplierSyncController::class, 'bulkCancel'])->name('bulk-cancel');
            Route::delete('/bulk-delete', [SupplierSyncController::class, 'bulkDestroy'])->name('bulk-delete');
            Route::delete('/{batchId}/logs/bulk-delete', [SupplierSyncController::class, 'bulkDestroyLogs'])->whereNumber('batchId')->name('logs.bulk-delete');
            Route::get('/{batchId}/logs/{logId}', [SupplierSyncController::class, 'showLog'])->whereNumber(['batchId', 'logId'])->name('logs.show');
            Route::get('/{batchId}', [SupplierSyncController::class, 'show'])->name('show')->where('batchId', '[0-9]+');
            Route::post('/start', [SupplierSyncController::class, 'startSync'])->name('start');
            Route::post('/cancel/{batchId}', [SupplierSyncController::class, 'cancelSync'])->name('cancel');
            Route::post('/retry/{batchId}', [SupplierSyncController::class, 'retrySync'])->name('retry');
            Route::get('/progress/{batchId}', [SupplierSyncController::class, 'getSyncProgress'])->name('progress');
        });

        // ================================================================
        // AI COST MONITORING — suppliers.monitoring.view
        // ================================================================
        Route::middleware(['can:suppliers.monitoring.view'])->prefix('monitoring')->name('monitoring.')->group(function () {
            Route::get('/', [AiCostMonitoringController::class, 'index'])->name('index');
            Route::get('/usage-stats', [AiCostMonitoringController::class, 'usageStats'])->name('usage-stats');
            Route::get('/top-prompts', [AiCostMonitoringController::class, 'topPrompts'])->name('top-prompts');
            Route::get('/budget-alerts', [AiCostMonitoringController::class, 'budgetAlerts'])->name('budget-alerts');
            Route::get('/logs', [AiCostMonitoringController::class, 'logs'])->name('logs');
            Route::post('/budget', [AiCostMonitoringController::class, 'updateBudget'])
                ->middleware('can:suppliers.monitoring.manage')
                ->name('budget.update');
            Route::post('/budgets', [AiCostMonitoringController::class, 'updateBudgets'])
                ->middleware('can:suppliers.monitoring.manage')
                ->name('budgets.update');
        });

    });

});

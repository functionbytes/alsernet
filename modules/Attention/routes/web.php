<?php

use Illuminate\Support\Facades\Route;
use Modules\Attention\Http\Controllers\AttentionCategoriesController;
use Modules\Attention\Http\Controllers\AttentionConfigurationController;
use Modules\Attention\Http\Controllers\AttentionDashboardController;
use Modules\Attention\Http\Controllers\AttentionDepartmentsController;
use Modules\Attention\Http\Controllers\AttentionEmailController;
use Modules\Attention\Http\Controllers\AttentionPublicController;
use Modules\Attention\Http\Controllers\AttentionRoutingRulesController;
use Modules\Attention\Http\Controllers\AttentionSedesController;
use Modules\Attention\Http\Controllers\AttentionSlaPoliciesController;
use Modules\Attention\Http\Controllers\AttentionTypesController;
use Modules\Attention\Http\Controllers\AttentionWebController;

/*
|--------------------------------------------------------------------------
| Web Routes - Attention Module
|--------------------------------------------------------------------------
|
| Rutas web del módulo Attention organizadas en tres secciones:
| 1. Públicas (pqrsf.*) - Sin autenticación, accesibles por ciudadanos
| 2. Operaciones PQRSF (attentions.*) - Panel settings
| 3. Configuración (settings.attention.*) - Usuarios settings (todos los roles internos)
|
*/

// ===== RUTAS PÚBLICAS (pqrsf.*) - Sin autenticación =====
Route::middleware(['web', 'throttle:30,1'])->prefix('pqrsf')->name('pqrsf.')->group(function () {
    Route::get('/', [AttentionPublicController::class, 'form'])->name('form');
    Route::post('/', [AttentionPublicController::class, 'submit'])->name('submit')->middleware('throttle:10,1');
    Route::get('/success/{radicado}', [AttentionPublicController::class, 'showSuccess'])->name('success');
    Route::get('/tracking', [AttentionPublicController::class, 'trackingForm'])->name('tracking');
    Route::get('/tracking/{radicado}', [AttentionPublicController::class, 'trackingResult'])->name('tracking.result');
});

// ===== RUTAS AUTENTICADAS (panel settings) =====
Route::middleware(['web', 'auth'])->group(function () {

    // ===== DASHBOARD ANALÍTICO =====
    Route::get('panel/attentions/dashboard', [AttentionDashboardController::class, 'index'])->name('attention.dashboard');
    Route::get('panel/attentions/dashboard/chart-data', [AttentionDashboardController::class, 'chartData'])->name('attention.dashboard.chart-data');

    // ===== OPERACIONES PQRSF (attention.*) =====
    Route::prefix('panel/attentions')->name('attention.')->group(function () {

        // Listados
        Route::get('/', [AttentionWebController::class, 'pending'])->name('pending');
        Route::get('/pending', [AttentionWebController::class, 'index'])->name('index');

        // Exportación
        Route::get('/export', [AttentionWebController::class, 'export'])->name('export');
        Route::get('/export/download', [AttentionWebController::class, 'exportDownload'])->name('export.download');

        // Bulk actions (AJAX)
        Route::post('/bulk-action', [AttentionWebController::class, 'bulkAction'])->name('bulk-action');

        // Crear y guardar
        Route::get('/create', [AttentionWebController::class, 'create'])->name('create');
        Route::post('/', [AttentionWebController::class, 'store'])->name('store');

        // Tracking (debe estar ANTES de las rutas con {uid} genérico)
        Route::get('/tracking/{radicado?}', [AttentionWebController::class, 'tracking'])->name('tracking');

        // Vista, edición y gestión
        Route::get('/show/{uid}', [AttentionWebController::class, 'show'])->name('show');
        Route::get('/{uid}/edit', [AttentionWebController::class, 'edit'])->name('edit');
        Route::get('/{uid}', [AttentionWebController::class, 'show'])->name('view');
        Route::get('/manage/{uid}', [AttentionWebController::class, 'manage'])->name('manage');

        // Notes (AJAX)
        Route::post('/{uid}/notes', [AttentionWebController::class, 'addNote'])->name('notes.add');
        Route::put('/{uid}/notes/{noteId}', [AttentionWebController::class, 'updateNote'])->name('notes.update');
        Route::delete('/{uid}/notes/{noteId}', [AttentionWebController::class, 'deleteNote'])->name('notes.delete');

        // Management (AJAX)
        Route::put('/{uid}/manage', [AttentionWebController::class, 'updateManagement'])->name('manage.update');

        // Files (AJAX)
        Route::post('/{uid}/upload-attachment', [AttentionWebController::class, 'uploadAttachment'])->name('upload-attachment');
        Route::delete('/{uid}/delete-attachment/{mediaId}', [AttentionWebController::class, 'deleteAttachment'])->name('delete-attachment');

        // Emails
        Route::prefix('{uid}/emails')->name('emails.')->group(function () {
            Route::get('/', [AttentionEmailController::class, 'index'])->name('index');
            Route::get('/{mailUid}/preview', [AttentionEmailController::class, 'preview'])->name('preview');
            Route::post('/{mailUid}/resend', [AttentionEmailController::class, 'resend'])->name('resend');
            Route::post('/send-custom', [AttentionEmailController::class, 'sendCustom'])->name('send-custom');
            Route::post('/send-confirmation', [AttentionEmailController::class, 'sendConfirmation'])->name('send-confirmation');
            Route::post('/send-in-process', [AttentionEmailController::class, 'sendInProcess'])->name('send-in-process');
            Route::post('/send-resolution', [AttentionEmailController::class, 'sendResolution'])->name('send-resolution');
        });

        // Survey
        Route::get('/survey/{radicado}', [AttentionWebController::class, 'survey'])->name('survey');
        Route::post('/survey/{radicado}', [AttentionWebController::class, 'storeSurvey'])->name('survey.store');
    });

    // ===== CONFIGURACIÓN (settings.attention.*) - ADMIN ONLY =====
    Route::prefix('panel/settings/attention')->name('settings.attention.')
        ->middleware(['settings'])->group(function () {

            // HUB
            Route::get('/', [AttentionConfigurationController::class, 'index'])->name('index');

            // TIPOS PQRSF
            Route::prefix('types')->name('types.')->group(function () {
                Route::get('/', [AttentionTypesController::class, 'index'])->name('index');
                Route::get('/create', [AttentionTypesController::class, 'create'])->name('create');
                Route::post('/', [AttentionTypesController::class, 'store'])->name('store');
                Route::get('/{type}', [AttentionTypesController::class, 'show'])->name('show');
                Route::get('/{type}/edit', [AttentionTypesController::class, 'edit'])->name('edit');
                Route::patch('/{type}', [AttentionTypesController::class, 'update'])->name('update');
                Route::delete('/{type}', [AttentionTypesController::class, 'destroy'])->name('destroy');
                Route::post('/{type}/toggle', [AttentionTypesController::class, 'toggle'])->name('toggle');
                Route::post('/reorder', [AttentionTypesController::class, 'reorder'])->name('reorder');
                Route::post('/bulk-action', [AttentionTypesController::class, 'bulkAction'])->name('bulk-action');
            });

            // CATEGORÍAS
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [AttentionCategoriesController::class, 'index'])->name('index');
                Route::get('/create', [AttentionCategoriesController::class, 'create'])->name('create');
                Route::post('/', [AttentionCategoriesController::class, 'store'])->name('store');
                Route::get('/{category}', [AttentionCategoriesController::class, 'show'])->name('show');
                Route::get('/{category}/edit', [AttentionCategoriesController::class, 'edit'])->name('edit');
                Route::patch('/{category}', [AttentionCategoriesController::class, 'update'])->name('update');
                Route::delete('/{category}', [AttentionCategoriesController::class, 'destroy'])->name('destroy');
                Route::post('/{category}/toggle', [AttentionCategoriesController::class, 'toggle'])->name('toggle');
                Route::post('/bulk-action', [AttentionCategoriesController::class, 'bulkAction'])->name('bulk-action');
            });

            // DEPARTAMENTOS
            Route::prefix('departments')->name('departments.')->group(function () {
                Route::get('/', [AttentionDepartmentsController::class, 'index'])->name('index');
                Route::get('/create', [AttentionDepartmentsController::class, 'create'])->name('create');
                Route::post('/', [AttentionDepartmentsController::class, 'store'])->name('store');
                Route::get('/{department}', [AttentionDepartmentsController::class, 'show'])->name('show');
                Route::get('/{department}/edit', [AttentionDepartmentsController::class, 'edit'])->name('edit');
                Route::patch('/{department}', [AttentionDepartmentsController::class, 'update'])->name('update');
                Route::delete('/{department}', [AttentionDepartmentsController::class, 'destroy'])->name('destroy');
                Route::post('/{department}/toggle', [AttentionDepartmentsController::class, 'toggle'])->name('toggle');
                Route::post('/bulk-action', [AttentionDepartmentsController::class, 'bulkAction'])->name('bulk-action');
            });

            // SEDES
            Route::prefix('sedes')->name('sedes.')->group(function () {
                Route::get('/', [AttentionSedesController::class, 'index'])->name('index');
                Route::get('/create', [AttentionSedesController::class, 'create'])->name('create');
                Route::post('/', [AttentionSedesController::class, 'store'])->name('store');
                Route::get('/{sede}', [AttentionSedesController::class, 'show'])->name('show');
                Route::get('/{sede}/edit', [AttentionSedesController::class, 'edit'])->name('edit');
                Route::patch('/{sede}', [AttentionSedesController::class, 'update'])->name('update');
                Route::delete('/{sede}', [AttentionSedesController::class, 'destroy'])->name('destroy');
                Route::post('/{sede}/toggle', [AttentionSedesController::class, 'toggle'])->name('toggle');
                Route::post('/bulk-action', [AttentionSedesController::class, 'bulkAction'])->name('bulk-action');
            });

            // CONFIGURACIONES GLOBALES
            Route::prefix('configurations')->name('configurations.')->group(function () {
                Route::get('/', [AttentionConfigurationController::class, 'globalSettings'])->name('global');
                Route::patch('/', [AttentionConfigurationController::class, 'updateGlobalSettings'])->name('update');
                Route::get('/email', [AttentionConfigurationController::class, 'emailSettings'])->name('email');
                Route::patch('/email', [AttentionConfigurationController::class, 'updateEmailSettings'])->name('email.update');
                Route::get('/sla', [AttentionConfigurationController::class, 'slaSettings'])->name('sla');
                Route::patch('/sla', [AttentionConfigurationController::class, 'updateSlaSettings'])->name('sla.update');
                Route::post('/reset', [AttentionConfigurationController::class, 'resetToDefaults'])->name('reset');
                Route::post('/email/test', [AttentionConfigurationController::class, 'testEmailConnection'])->name('email.test');
            });

            // PLANTILLAS EMAIL
            Route::get('/templates', [AttentionConfigurationController::class, 'templatesIndex'])->name('templates.index');
            Route::get('/templates/search', [AttentionConfigurationController::class, 'searchTemplates'])->name('templates.search');

            // REGLAS DE ASIGNACIÓN AUTOMÁTICA
            Route::prefix('routing-rules')->name('routing-rules.')->group(function () {
                Route::get('/', [AttentionRoutingRulesController::class, 'index'])->name('index');
                Route::get('/create', [AttentionRoutingRulesController::class, 'create'])->name('create');
                Route::post('/', [AttentionRoutingRulesController::class, 'store'])->name('store');
                Route::post('/bulk-action', [AttentionRoutingRulesController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/{routingRule}/edit', [AttentionRoutingRulesController::class, 'edit'])->name('edit');
                Route::put('/{routingRule}', [AttentionRoutingRulesController::class, 'update'])->name('update');
                Route::delete('/{routingRule}', [AttentionRoutingRulesController::class, 'destroy'])->name('destroy');
                Route::post('/{routingRule}/toggle', [AttentionRoutingRulesController::class, 'toggle'])->name('toggle');
            });

            // POLÍTICAS SLA
            Route::prefix('sla-policies')->name('sla-policies.')->group(function () {
                Route::get('/', [AttentionSlaPoliciesController::class, 'index'])->name('index');
                Route::get('/create', [AttentionSlaPoliciesController::class, 'create'])->name('create');
                Route::post('/', [AttentionSlaPoliciesController::class, 'store'])->name('store');
                Route::get('/{policy}', [AttentionSlaPoliciesController::class, 'show'])->name('show');
                Route::get('/{policy}/edit', [AttentionSlaPoliciesController::class, 'edit'])->name('edit');
                Route::patch('/{policy}', [AttentionSlaPoliciesController::class, 'update'])->name('update');
                Route::delete('/{policy}', [AttentionSlaPoliciesController::class, 'destroy'])->name('destroy');
                Route::post('/bulk-action', [AttentionSlaPoliciesController::class, 'bulkAction'])->name('bulk-action');
            });
        });

    // ===== GESTIÓN GLOBAL DE EMAILS (emails.*) - ADMIN =====
    Route::prefix('panel/emails')->name('emails.')
        ->middleware(['settings'])->group(function () {
            Route::get('/history', [AttentionEmailController::class, 'history'])->name('history');
            Route::get('/stats', [AttentionEmailController::class, 'stats'])->name('stats');
            Route::post('/export', [AttentionEmailController::class, 'export'])->name('export');
        });
});

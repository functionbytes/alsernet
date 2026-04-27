<?php

use Illuminate\Support\Facades\Route;
use Modules\Attention\Http\Controllers\AttentionBulkApiController;
use Modules\Attention\Http\Controllers\AttentionCategoriesController;
use Modules\Attention\Http\Controllers\AttentionController;
use Modules\Attention\Http\Controllers\AttentionDepartmentsController;
use Modules\Attention\Http\Controllers\AttentionEmailController;
use Modules\Attention\Http\Controllers\AttentionExportApiController;
use Modules\Attention\Http\Controllers\AttentionFileController;
use Modules\Attention\Http\Controllers\AttentionNotificationApiController;
use Modules\Attention\Http\Controllers\AttentionPublicApiController;
use Modules\Attention\Http\Controllers\AttentionSedesController;
use Modules\Attention\Http\Controllers\AttentionSlaApiController;
use Modules\Attention\Http\Controllers\AttentionSlaPoliciesController;
use Modules\Attention\Http\Controllers\AttentionStatsApiController;
use Modules\Attention\Http\Controllers\AttentionTypesController;

/*
|--------------------------------------------------------------------------
| API Routes - Attention Module
|--------------------------------------------------------------------------
|
| Rutas API del módulo Attention organizadas en dos secciones:
| 1. Públicas (rate limited 60/min)
| 2. Autenticadas (gestión de atenciones)
|
*/

// ===== PÚBLICAS (Rate limited 60/min) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('peticiones')->name('api.peticiones.')->group(function () {
    Route::post('/submit', [AttentionPublicApiController::class, 'submit'])->name('submit');
    Route::get('/track/{radicado}', [AttentionPublicApiController::class, 'track'])->name('track');
    Route::post('/{radicado}/satisfaction', [AttentionPublicApiController::class, 'submitSatisfaction'])->name('satisfaction');
});

// ===== AUTENTICADAS =====
Route::middleware(['api', 'auth:sanctum'])->prefix('attentions')->name('api.attentions.')->group(function () {

    // Listado principal
    Route::get('/', [AttentionController::class, 'index'])->name('index');

    // Stats y reporting (antes de /{radicado} para evitar captura por wildcard)
    Route::get('/stats', [AttentionStatsApiController::class, 'stats'])->name('stats');
    Route::get('/stats/dashboard', [AttentionStatsApiController::class, 'dashboardStats'])->name('stats.dashboard');
    Route::get('/stats/by-type', [AttentionStatsApiController::class, 'statsByType'])->name('stats.by-type');
    Route::get('/stats/by-status', [AttentionStatsApiController::class, 'statsByStatus'])->name('stats.by-status');

    // SLA (antes de /{radicado})
    Route::get('/sla-breaches', [AttentionSlaApiController::class, 'slaBreaches'])->name('sla-breaches');

    // Bulk actions (antes de /{radicado})
    Route::post('/bulk-assign', [AttentionBulkApiController::class, 'bulkAssign'])->name('bulk-assign');
    Route::post('/bulk-close', [AttentionBulkApiController::class, 'bulkClose'])->name('bulk-close');
    Route::delete('/bulk-delete', [AttentionBulkApiController::class, 'bulkDelete'])->name('bulk-delete');

    // Export (antes de /{radicado})
    Route::post('/export', [AttentionExportApiController::class, 'export'])->name('export');
    Route::get('/export/{token}', [AttentionExportApiController::class, 'downloadExport'])->name('export.download');

    // Rutas con parámetro {radicado}
    Route::get('/{radicado}', [AttentionController::class, 'show'])->name('show');
    Route::patch('/{radicado}', [AttentionController::class, 'update'])->name('update');

    // Asignación
    Route::post('/{radicado}/assign-department', [AttentionController::class, 'assignDepartment'])->name('assign-department');
    Route::post('/{radicado}/assign-user', [AttentionController::class, 'assignUser'])->name('assign-user');

    // Cambio de estado
    Route::post('/{radicado}/change-status', [AttentionController::class, 'changeStatus'])->name('change-status');
    Route::post('/{radicado}/resolve', [AttentionController::class, 'resolve'])->name('resolve');
    Route::post('/{radicado}/close', [AttentionController::class, 'close'])->name('close');

    // Notas
    Route::get('/{radicado}/notes', [AttentionController::class, 'getNotes'])->name('notes');
    Route::post('/{radicado}/notes', [AttentionController::class, 'addNote'])->name('notes.add');

    // Emails — Sistema completo de gestión de emails
    Route::prefix('{radicado}/emails')->name('emails.')->group(function () {
        Route::get('/', [AttentionEmailController::class, 'index'])->name('index');
        Route::post('/send-custom', [AttentionEmailController::class, 'sendCustom'])->name('send-custom');
        Route::post('/{mailUid}/resend', [AttentionEmailController::class, 'resend'])->name('resend');
    });

    // Emails legacy
    Route::post('/{radicado}/send-confirmation', [AttentionNotificationApiController::class, 'sendConfirmation'])->name('send-confirmation');
    Route::post('/{radicado}/send-resolution', [AttentionNotificationApiController::class, 'sendResolution'])->name('send-resolution');

    // Archivos
    Route::post('/{radicado}/files', [AttentionFileController::class, 'upload'])->name('files.upload');
    Route::get('/{radicado}/files', [AttentionFileController::class, 'list'])->name('files.list');
    Route::delete('/{radicado}/files/{mediaId}', [AttentionFileController::class, 'delete'])->name('files.delete');

    // Historial
    Route::get('/{radicado}/actions', [AttentionController::class, 'getActions'])->name('actions');
    Route::get('/{radicado}/emails', [AttentionController::class, 'getEmails'])->name('emails');

    // SLA por radicado
    Route::get('/{radicado}/sla-status', [AttentionSlaApiController::class, 'slaStatus'])->name('sla-status');
});

// ===== CONFIGURACIÓN (autenticadas) =====
Route::middleware(['api', 'auth:sanctum'])->prefix('config')->name('api.config.')->group(function () {

    // Types
    Route::get('/types', [AttentionTypesController::class, 'index'])->name('types.index');
    Route::post('/types', [AttentionTypesController::class, 'store'])->name('types.store');
    Route::get('/types/{type}', [AttentionTypesController::class, 'show'])->name('types.show');
    Route::patch('/types/{type}', [AttentionTypesController::class, 'update'])->name('types.update');
    Route::delete('/types/{type}', [AttentionTypesController::class, 'destroy'])->name('types.destroy');
    Route::post('/types/{type}/toggle', [AttentionTypesController::class, 'toggle'])->name('types.toggle');

    // Categories
    Route::get('/categories', [AttentionCategoriesController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AttentionCategoriesController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}', [AttentionCategoriesController::class, 'show'])->name('categories.show');
    Route::patch('/categories/{category}', [AttentionCategoriesController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AttentionCategoriesController::class, 'destroy'])->name('categories.destroy');

    // Departments
    Route::get('/departments', [AttentionDepartmentsController::class, 'index'])->name('departments.index');
    Route::post('/departments', [AttentionDepartmentsController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}', [AttentionDepartmentsController::class, 'show'])->name('departments.show');
    Route::patch('/departments/{department}', [AttentionDepartmentsController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [AttentionDepartmentsController::class, 'destroy'])->name('departments.destroy');
    Route::post('/departments/{department}/assign-user', [AttentionDepartmentsController::class, 'assignUser'])->name('departments.assign-user');
    Route::delete('/departments/{department}/remove-user/{user}', [AttentionDepartmentsController::class, 'removeUser'])->name('departments.remove-user');

    // Sedes
    Route::get('/sedes', [AttentionSedesController::class, 'index'])->name('sedes.index');
    Route::post('/sedes', [AttentionSedesController::class, 'store'])->name('sedes.store');
    Route::get('/sedes/{sede}', [AttentionSedesController::class, 'show'])->name('sedes.show');
    Route::patch('/sedes/{sede}', [AttentionSedesController::class, 'update'])->name('sedes.update');
    Route::delete('/sedes/{sede}', [AttentionSedesController::class, 'destroy'])->name('sedes.destroy');

    // SLA Policies
    Route::get('/sla-policies', [AttentionSlaPoliciesController::class, 'index'])->name('sla-policies.index');
    Route::post('/sla-policies', [AttentionSlaPoliciesController::class, 'store'])->name('sla-policies.store');
    Route::get('/sla-policies/{policy}', [AttentionSlaPoliciesController::class, 'show'])->name('sla-policies.show');
    Route::patch('/sla-policies/{policy}', [AttentionSlaPoliciesController::class, 'update'])->name('sla-policies.update');
    Route::delete('/sla-policies/{policy}', [AttentionSlaPoliciesController::class, 'destroy'])->name('sla-policies.destroy');
});

// ===== GESTIÓN GLOBAL DE EMAILS (autenticadas) =====
Route::middleware(['api', 'auth:sanctum'])->prefix('emails')->name('api.emails.')->group(function () {
    Route::get('/history', [AttentionEmailController::class, 'history'])->name('history');
    Route::get('/stats', [AttentionEmailController::class, 'stats'])->name('stats');
    Route::post('/export', [AttentionEmailController::class, 'export'])->name('export');
});

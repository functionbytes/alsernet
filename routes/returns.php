<?php

use App\Http\Controllers\Api\Return\PublicReturnController;
use App\Http\Controllers\Api\Return\ReturnController;
use App\Http\Controllers\Admin\AdminReturnController;
use Illuminate\Support\Facades\Route;

// Rutas públicas - sin autenticación
Route::prefix('api/returns')->group(function () {
    Route::get('/status/{code}', [PublicReturnController::class, 'getStatus']);
    Route::post('/guest', [PublicReturnController::class, 'createGuestReturn']);
    Route::get('/form-data', [PublicReturnController::class, 'getFormData']);
});

// Rutas autenticadas para clientes (API)
Route::middleware(['auth:sanctum'])->prefix('api/returns')->group(function () {
    Route::get('/', [ReturnController::class, 'index']);
    Route::get('/{id}', [ReturnController::class, 'show'])->middleware('check.return.access');
    Route::post('/', [ReturnController::class, 'store'])->middleware('permission:returns.create');
    Route::put('/{id}', [ReturnController::class, 'update'])->middleware('check.return.access');
    Route::post('/{id}/message', [ReturnController::class, 'addMessage'])->middleware('check.return.access');
    Route::post('/{id}/attachment', [ReturnController::class, 'uploadAttachment'])->middleware('check.return.access');
    Route::get('/{id}/pdf', [ReturnController::class, 'downloadPDF'])->middleware('check.return.access');
});

// Rutas administrativas web
Route::prefix('admin/returns')->middleware(['auth', 'role:super-admin|admin|manager|administrative'])->group(function () {
    // Listado y estadísticas
    Route::get('/', [AdminReturnController::class, 'index'])->name('admin.returns.index');
    Route::get('/statistics', [AdminReturnController::class, 'getStatistics'])
        ->middleware('permission:dashboard.statistics')
        ->name('admin.returns.statistics');

    // CRUD básico
    Route::get('/create', [AdminReturnController::class, 'create'])
        ->middleware('permission:returns.create')
        ->name('admin.returns.create');
    Route::post('/', [AdminReturnController::class, 'store'])
        ->middleware('permission:returns.create')
        ->name('admin.returns.store');
    Route::get('/{id}', [AdminReturnController::class, 'show'])
        ->middleware('check.return.access')
        ->name('admin.returns.show');
    Route::get('/{id}/edit', [AdminReturnController::class, 'edit'])
        ->middleware('check.return.access')
        ->name('admin.returns.edit');
    Route::put('/{id}', [AdminReturnController::class, 'update'])
        ->middleware('check.return.access')
        ->name('admin.returns.update');
    Route::delete('/{id}', [AdminReturnController::class, 'destroy'])
        ->middleware('permission:returns.delete')
        ->name('admin.returns.destroy');

    // Gestión de estados
    Route::post('/{id}/status', [AdminReturnController::class, 'updateStatus'])
        ->middleware('check.return.access')
        ->name('admin.returns.status.update');
    Route::post('/{id}/approve', [AdminReturnController::class, 'approve'])
        ->middleware('permission:returns.status.approve')
        ->name('admin.returns.approve');
    Route::post('/{id}/reject', [AdminReturnController::class, 'reject'])
        ->middleware('permission:returns.status.reject')
        ->name('admin.returns.reject');

    // Asignación
    Route::post('/{id}/assign', [AdminReturnController::class, 'assign'])
        ->middleware('permission:returns.assign')
        ->name('admin.returns.assign');

    // Comunicación
    Route::post('/{id}/discussion', [AdminReturnController::class, 'addDiscussion'])
        ->middleware('check.return.access')
        ->name('admin.returns.discussion.add');
    Route::post('/{id}/attachment', [AdminReturnController::class, 'uploadAttachment'])
        ->middleware('check.return.access')
        ->name('admin.returns.attachment.upload');

    // Pagos
    Route::get('/{id}/payments', [AdminReturnController::class, 'getPayments'])
        ->middleware('permission:payments.view')
        ->name('admin.returns.payments');
    Route::post('/{id}/payment', [AdminReturnController::class, 'addPayment'])
        ->middleware('permission:payments.create')
        ->name('admin.returns.payment.add');

    // Exportación
    Route::get('/export', [AdminReturnController::class, 'export'])
        ->middleware('permission:returns.export')
        ->name('admin.returns.export');
    Route::get('/{id}/pdf', [AdminReturnController::class, 'downloadPDF'])
        ->middleware('check.return.access')
        ->name('admin.returns.pdf');

    // Operaciones masivas
    Route::post('/bulk-update', [AdminReturnController::class, 'bulkUpdate'])
        ->middleware('permission:returns.bulk.update')
        ->name('admin.returns.bulk.update');
});

// Configuración del sistema de devoluciones
Route::prefix('admin/returns/config')->middleware(['auth', 'role:super-admin|admin'])->group(function () {
    // Estados
    Route::get('/status', [AdminReturnController::class, 'getStatuses'])
        ->middleware('permission:config.status.manage')
        ->name('admin.returns.config.status');
    Route::post('/status', [AdminReturnController::class, 'createStatus'])
        ->middleware('permission:config.status.manage')
        ->name('admin.returns.config.status.create');
    Route::put('/status/{id}', [AdminReturnController::class, 'updateStatus'])
        ->middleware('permission:config.status.manage')
        ->name('admin.returns.config.status.update');

    // Motivos
    Route::get('/reasons', [AdminReturnController::class, 'getReasons'])
        ->middleware('permission:config.reasons.manage')
        ->name('admin.returns.config.reasons');
    Route::post('/reasons', [AdminReturnController::class, 'createReason'])
        ->middleware('permission:config.reasons.manage')
        ->name('admin.returns.config.reasons.create');
    Route::put('/reasons/{id}', [AdminReturnController::class, 'updateReason'])
        ->middleware('permission:config.reasons.manage')
        ->name('admin.returns.config.reasons.update');

    // Tipos
    Route::get('/types', [AdminReturnController::class, 'getTypes'])
        ->middleware('permission:config.types.manage')
        ->name('admin.returns.config.types');
    Route::post('/types', [AdminReturnController::class, 'createType'])
        ->middleware('permission:config.types.manage')
        ->name('admin.returns.config.types.create');

    // Políticas
    Route::get('/policies', [AdminReturnController::class, 'getPolicies'])
        ->middleware('permission:config.policies.manage')
        ->name('admin.returns.config.policies');
    Route::post('/policies', [AdminReturnController::class, 'createPolicy'])
        ->middleware('permission:config.policies.manage')
        ->name('admin.returns.config.policies.create');
});

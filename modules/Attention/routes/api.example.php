<?php

use Illuminate\Support\Facades\Route;
use Modules\Attention\Http\Controllers\AttentionController;
use Modules\Attention\Http\Controllers\AttentionFileController;

/*
|--------------------------------------------------------------------------
| API Routes - Attention Module (PQRSF)
|--------------------------------------------------------------------------
|
| Este archivo contiene todas las rutas sugeridas para el módulo Attention.
| Copiar estas rutas a tu archivo routes/api.php principal o incluir este
| archivo usando require.
|
*/

// ============================================================================
// RUTAS PÚBLICAS - Sin autenticación
// ============================================================================

Route::prefix('pqrsf')->name('api.pqrsf.')->group(function () {

    // Crear nueva solicitud PQRSF (ciudadano)
    Route::post('/', [AttentionController::class, 'submit'])
        ->name('submit');

    // Consultar estado por radicado (ciudadano)
    Route::get('/{radicado}', [AttentionController::class, 'track'])
        ->name('track')
        ->where('radicado', '[A-Z0-9-]+');

    // Enviar encuesta de satisfacción (ciudadano)
    Route::post('/{radicado}/satisfaction', [AttentionController::class, 'submitSatisfaction'])
        ->name('satisfaction')
        ->where('radicado', '[A-Z0-9-]+');
});

// ============================================================================
// RUTAS ADMINISTRATIVAS - Requieren autenticación
// ============================================================================

Route::prefix('settings/pqrsf')
    ->name('api.settings.pqrsf.')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        // ========================================================================
        // GESTIÓN PRINCIPAL
        // ========================================================================

        // Listar todos los PQRSF con filtros y paginación
        Route::get('/', [AttentionController::class, 'index'])
            ->name('index');

        // Obtener estadísticas generales
        Route::get('/stats', [AttentionController::class, 'stats'])
            ->name('stats');

        // Ver detalle completo de un PQRSF
        Route::get('/{radicado}', [AttentionController::class, 'show'])
            ->name('show')
            ->where('radicado', '[A-Z0-9-]+');

        // Actualizar información básica
        Route::patch('/{radicado}', [AttentionController::class, 'update'])
            ->name('update')
            ->where('radicado', '[A-Z0-9-]+');

        // ========================================================================
        // ACCIONES DE GESTIÓN
        // ========================================================================

        // Asignar a departamento
        Route::post('/{radicado}/assign-department', [AttentionController::class, 'assignDepartment'])
            ->name('assign-department')
            ->where('radicado', '[A-Z0-9-]+');

        // Asignar a usuario específico
        Route::post('/{radicado}/assign-user', [AttentionController::class, 'assignUser'])
            ->name('assign-user')
            ->where('radicado', '[A-Z0-9-]+');

        // Cambiar estado manualmente
        Route::post('/{radicado}/change-status', [AttentionController::class, 'changeStatus'])
            ->name('change-status')
            ->where('radicado', '[A-Z0-9-]+');

        // Resolver con respuesta al ciudadano
        Route::post('/{radicado}/resolve', [AttentionController::class, 'resolve'])
            ->name('resolve')
            ->where('radicado', '[A-Z0-9-]+');

        // Cerrar definitivamente
        Route::post('/{radicado}/close', [AttentionController::class, 'close'])
            ->name('close')
            ->where('radicado', '[A-Z0-9-]+');

        // ========================================================================
        // NOTAS INTERNAS
        // ========================================================================

        // Agregar nota interna
        Route::post('/{radicado}/notes', [AttentionController::class, 'addNote'])
            ->name('notes.store')
            ->where('radicado', '[A-Z0-9-]+');

        // Listar todas las notas
        Route::get('/{radicado}/notes', [AttentionController::class, 'getNotes'])
            ->name('notes.index')
            ->where('radicado', '[A-Z0-9-]+');

        // ========================================================================
        // HISTORIAL Y AUDITORÍA
        // ========================================================================

        // Ver historial de acciones
        Route::get('/{radicado}/actions', [AttentionController::class, 'getActions'])
            ->name('actions')
            ->where('radicado', '[A-Z0-9-]+');

        // Ver emails enviados
        Route::get('/{radicado}/emails', [AttentionController::class, 'getEmails'])
            ->name('emails')
            ->where('radicado', '[A-Z0-9-]+');

        // ========================================================================
        // GESTIÓN DE ARCHIVOS
        // ========================================================================

        // Subir múltiples archivos
        Route::post('/{radicado}/files', [AttentionFileController::class, 'upload'])
            ->name('files.upload')
            ->where('radicado', '[A-Z0-9-]+');

        // Listar archivos del PQRSF
        Route::get('/{radicado}/files', [AttentionFileController::class, 'list'])
            ->name('files.index')
            ->where('radicado', '[A-Z0-9-]+');

        // Estadísticas de archivos
        Route::get('/{radicado}/files/stats', [AttentionFileController::class, 'stats'])
            ->name('files.stats')
            ->where('radicado', '[A-Z0-9-]+');

        // Eliminar múltiples archivos
        Route::post('/{radicado}/files/bulk-delete', [AttentionFileController::class, 'bulkDelete'])
            ->name('files.bulk-delete')
            ->where('radicado', '[A-Z0-9-]+');

        // Ver información de un archivo específico
        Route::get('/{radicado}/files/{mediaId}', [AttentionFileController::class, 'show'])
            ->name('files.show')
            ->where(['radicado' => '[A-Z0-9-]+', 'mediaId' => '[0-9]+']);

        // Descargar archivo
        Route::get('/{radicado}/files/{mediaId}/download', [AttentionFileController::class, 'download'])
            ->name('files.download')
            ->where(['radicado' => '[A-Z0-9-]+', 'mediaId' => '[0-9]+']);

        // Eliminar archivo individual
        Route::delete('/{radicado}/files/{mediaId}', [AttentionFileController::class, 'delete'])
            ->name('files.delete')
            ->where(['radicado' => '[A-Z0-9-]+', 'mediaId' => '[0-9]+']);
    });

// ============================================================================
// RUTAS CON MIDDLEWARE ADICIONAL (Ejemplo)
// ============================================================================

/*
// Ejemplo con middleware de permisos
Route::prefix('settings/pqrsf')
    ->middleware(['auth:sanctum', 'can:manage-pqrsf'])
    ->group(function () {
        // Rutas que requieren permiso específico
    });

// Ejemplo con rate limiting
Route::prefix('pqrsf')
    ->middleware('throttle:10,1') // 10 requests por minuto
    ->group(function () {
        Route::post('/', [AttentionController::class, 'submit']);
    });
*/

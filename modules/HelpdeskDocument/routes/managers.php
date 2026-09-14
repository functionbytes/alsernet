<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskDocument\Http\Controllers\Managers\ChatGalleryDocumentController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentActionController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentCreateController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentFileController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentPanelController;

// Todo el archivo esta detras del toggle "Settings > Integraciones" de
// Document (helpdesk_document_enabled()) — antes solo DocumentPanelController
// llevaba un abort_if() a mano y DocumentFileController::destroy() no tenia
// ningun check, dejando el borrado de archivos alcanzable con la integracion
// desactivada.
Route::middleware('integration.enabled:document')->group(function () {
    // Lectura del expediente (panel docs-rp cargado vía AJAX en el inbox):
    // basta con poder VER la conversación.
    Route::get('/conversations/{conversation}/documents/{document}/panel', [DocumentPanelController::class, 'show'])
        ->middleware('can:helpdesk.conversations.view')
        ->name('manager.helpdesk.conversations.documents.panel');

    // Lecturas del expediente que antes iban directas a api.documents.* (rol
    // super-admin|supervisor): un agente normal recibía 403 en "Ver historial
    // completo" y "Descargar todo". Igual que el panel, basta con ver la
    // conversación; el ownership por email se verifica en el controller.
    Route::get('/conversations/{conversation}/documents/{document}/action-history', [DocumentActionController::class, 'actionHistory'])
        ->middleware(['throttle:60,1', 'can:helpdesk.conversations.view'])
        ->name('manager.helpdesk.conversations.documents.action-history');

    Route::get('/conversations/{conversation}/documents/{document}/download-zip', [DocumentActionController::class, 'downloadZip'])
        ->middleware(['throttle:30,1', 'can:helpdesk.conversations.view'])
        ->name('manager.helpdesk.conversations.documents.download-zip');

    // A partir de aquí, todo son acciones que MUTAN el expediente
    // (aprobar/rechazar/enviar/subir/eliminar): exigen helpdesk.documents.manage,
    // no basta con ver la conversación (antes cualquiera con conversations.view
    // podía aprobar/eliminar un expediente KYC).

    // Borrar un archivo del expediente (autenticado; reemplaza el endpoint público api.documents.files.delete)
    Route::delete('/conversations/{conversation}/documents/{document}/files/{docType}', [DocumentFileController::class, 'destroy'])
        ->middleware('can:helpdesk.documents.manage')
        ->name('manager.helpdesk.conversations.documents.files.destroy');

    // Refresco AJAX de la lista de expedientes del tab
    Route::get('/conversations/{conversation}/documents/list', [DocumentPanelController::class, 'list'])
        ->middleware(['throttle:60,1', 'can:helpdesk.conversations.view'])
        ->name('manager.helpdesk.conversations.documents.list');

    // Crear un expediente nuevo para el cliente de la conversación desde el inbox
    Route::get('/conversations/{conversation}/documents/create-options', [DocumentCreateController::class, 'options'])
        ->middleware('can:helpdesk.documents.manage')
        ->name('manager.helpdesk.conversations.documents.create-options');

    Route::post('/conversations/{conversation}/documents', [DocumentCreateController::class, 'store'])
        ->middleware(['throttle:30,1', 'can:helpdesk.documents.manage'])
        ->name('manager.helpdesk.conversations.documents.store');

    // Buscar expedientes existentes (por nº de orden/uid) para ASIGNAR uno a la
    // conversación desde el modal — feed AJAX del select2.
    Route::get('/conversations/{conversation}/documents/search', [DocumentCreateController::class, 'search'])
        ->middleware(['throttle:60,1', 'can:helpdesk.documents.manage'])
        ->name('manager.helpdesk.conversations.documents.search');

    // Cargar documentos desde la galería del chat (importar adjuntos como documento)
    Route::post('/conversations/{conversation}/documents/import-from-chat', [ChatGalleryDocumentController::class, 'importFromChat'])
        ->middleware(['throttle:30,1', 'can:helpdesk.documents.manage'])
        ->name('manager.helpdesk.conversations.documents.import-from-chat');

    // Subir documentos desde el equipo del agente (multipart files[])
    Route::post('/conversations/{conversation}/documents/import-from-device', [ChatGalleryDocumentController::class, 'importFromDevice'])
        ->middleware(['throttle:30,1', 'can:helpdesk.documents.manage'])
        ->name('manager.helpdesk.conversations.documents.import-from-device');

    // Acciones mutadoras del expediente — proxies helpdesk-scoped que reemplazan las
    // rutas api.documents.* (solo auth:web). Cada una verifica el permiso helpdesk y
    // el ownership cliente/email antes de delegar en el módulo Document. No cambian la
    // respuesta JSON: el JS del tab/modales sigue funcionando vía sus data-url-*.
    Route::prefix('/conversations/{conversation}/documents/{document}')
        ->middleware(['throttle:60,1', 'can:helpdesk.documents.manage'])
        ->name('manager.helpdesk.conversations.documents.')
        ->group(function () {
            // Asignar un expediente EXISTENTE a la conversación (vincular, no crear).
            Route::post('/link', [DocumentCreateController::class, 'link'])->name('link');
            Route::post('/upload', [DocumentActionController::class, 'upload'])->name('upload');
            Route::post('/assign', [DocumentActionController::class, 'assign'])->name('assign');
            Route::post('/approve-stage', [DocumentActionController::class, 'approveStage'])->name('approve-stage');
            Route::post('/reject-stage', [DocumentActionController::class, 'rejectStage'])->name('reject-stage');
            Route::post('/send-notification', [DocumentActionController::class, 'sendNotification'])->name('send-notification');
            Route::post('/send-reminder', [DocumentActionController::class, 'sendReminder'])->name('send-reminder');
            Route::post('/send-upload-confirmation', [DocumentActionController::class, 'sendUploadConfirmation'])->name('send-upload-confirmation');
            Route::post('/send-approval', [DocumentActionController::class, 'sendApproval'])->name('send-approval');
            Route::post('/send-missing', [DocumentActionController::class, 'sendMissing'])->name('send-missing');
            Route::post('/send-rejection', [DocumentActionController::class, 'sendRejection'])->name('send-rejection');
            Route::post('/send-custom-email', [DocumentActionController::class, 'sendCustomEmail'])->name('send-custom-email');
            Route::post('/notes', [DocumentActionController::class, 'addNote'])->name('notes.add');
            Route::delete('/notes/{noteId}', [DocumentActionController::class, 'deleteNote'])->name('notes.delete');
            Route::post('/upload-attachment', [DocumentActionController::class, 'uploadAttachment'])->name('upload-attachment');
            Route::delete('/delete-attachment/{mediaId}', [DocumentActionController::class, 'deleteAttachment'])->name('delete-attachment');
            Route::post('/update', [DocumentActionController::class, 'update'])->name('update');
        });
});

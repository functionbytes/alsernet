<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskDocument\Http\Controllers\Managers\ChatGalleryDocumentController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentActionController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentFileController;
use Modules\HelpdeskDocument\Http\Controllers\Managers\DocumentPanelController;

// Detalle de un expediente del cliente (panel docs-rp cargado vía AJAX en el inbox)
Route::get('/conversations/{conversation}/documents/{document}/panel', [DocumentPanelController::class, 'show'])
    ->middleware('can:helpdesk.conversations.view')
    ->name('manager.helpdesk.conversations.documents.panel');

// Borrar un archivo del expediente (autenticado; reemplaza el endpoint público api.documents.files.delete)
Route::delete('/conversations/{conversation}/documents/{document}/files/{docType}', [DocumentFileController::class, 'destroy'])
    ->middleware('can:helpdesk.conversations.view')
    ->name('manager.helpdesk.conversations.documents.files.destroy');

// Cargar documentos desde la galería del chat (importar adjuntos como documento)
Route::post('/conversations/{conversation}/documents/import-from-chat', [ChatGalleryDocumentController::class, 'importFromChat'])
    ->middleware(['throttle:30,1', 'can:helpdesk.conversations.view'])
    ->name('manager.helpdesk.conversations.documents.import-from-chat');

// Acciones mutadoras del expediente — proxies helpdesk-scoped que reemplazan las
// rutas api.documents.* (solo auth:web). Cada una verifica el permiso helpdesk y
// el ownership cliente/email antes de delegar en el módulo Document. No cambian la
// respuesta JSON: el JS del tab/modales sigue funcionando vía sus data-url-*.
Route::prefix('/conversations/{conversation}/documents/{document}')
    ->middleware(['throttle:60,1', 'can:helpdesk.conversations.view'])
    ->name('manager.helpdesk.conversations.documents.')
    ->group(function () {
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

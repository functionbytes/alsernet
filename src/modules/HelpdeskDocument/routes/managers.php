<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskDocument\Http\Controllers\Managers\ChatGalleryDocumentController;
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

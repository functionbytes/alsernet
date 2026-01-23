<?php

use Illuminate\Support\Facades\Route;
use Modules\Document\Http\Controllers\Api\DocumentsController;
use Modules\Document\Http\Controllers\Api\DocumentValidationController;

// ✅ Public routes with rate limiting (stateless API)
Route::middleware(['api', 'throttle:60,1'])->group(function () {
    // RESTful endpoint: POST /api/documents → creates a new document
    Route::post('/', [DocumentsController::class, 'store'])->name('store');
    // Deprecated: POST /api/documents/create (kept for backwards compatibility)
    Route::post('/create', [DocumentsController::class, 'store'])->name('create');
    // Process endpoint: Handles action-based requests (deprecated)
    Route::post('/process', [DocumentsController::class, 'process'])->name('process');
    Route::get('/verify', [DocumentsController::class, 'verify'])->name('verify');
    Route::get('/{uid}/validation', [DocumentsController::class, 'validation'])->name('validation');
    Route::post('/{uid}/files', [DocumentsController::class, 'uploadFiles'])->name('files.upload');
    Route::delete('/{uid}/files/{docType}', [DocumentsController::class, 'deleteFile'])->name('files.delete.by-type');
    Route::post('/webhooks/prestashop/order-paid', [DocumentsController::class, 'prestashopOrderPaid'])->name('webhooks.prestashop.order-paid');
});

// Authenticated routes - requires user authentication with session
// ✅ Usar 'web' para soporte de sesión + 'auth:web' para autenticación con sesión
// IMPORTANTE: NO usar middleware 'api' junto con 'web' - son incompatibles
Route::middleware(['web', 'auth:web'])->group(function () {
    // Document processing and syncing
    Route::get('/order/data/{order_id}', [DocumentsController::class, 'getOrderData'])->name('order.data');
    Route::post('/fill-order-data', [DocumentsController::class, 'fillDocumentWithOrderData'])->name('fill-order-data');
    Route::get('/sync/all', [DocumentsController::class, 'syncAllDocumentsWithOrders'])->name('sync.all');
    Route::get('/sync/by-query', [DocumentsController::class, 'syncDocumentsByOrderQuery'])->name('sync.by-query');
    Route::post('/sync/by-order', [DocumentsController::class, 'syncDocumentByOrderId'])->name('sync.by-order');

    // Document reminders and confirmations
    Route::post('/resend-reminder', [DocumentsController::class, 'resendDocumentReminder'])->name('resend-reminder');
    Route::post('/confirm-upload', [DocumentsController::class, 'confirmDocumentUpload'])->name('confirm-upload');

    // Validation workflow actions
    Route::post('/{uid}/approve-stage', [DocumentValidationController::class, 'approveStage'])->name('approve-stage');
    Route::post('/{uid}/reject-stage', [DocumentValidationController::class, 'rejectStage'])->name('reject-stage');

    // Email operations
    Route::post('/{uid}/send-approval', [DocumentValidationController::class, 'sendApproval'])->name('send-approval');
    Route::post('/{uid}/send-rejection', [DocumentValidationController::class, 'sendRejection'])->name('send-rejection');
    Route::post('/{uid}/send-custom-email', [DocumentValidationController::class, 'sendCustomEmail'])->name('send-custom-email');
    Route::post('/{uid}/send-reminder', [DocumentValidationController::class, 'sendReminder'])->name('send-reminder');
    Route::post('/{uid}/send-notification', [DocumentValidationController::class, 'sendNotification'])->name('send-notification');
    Route::post('/{uid}/send-upload-confirmation', [DocumentValidationController::class, 'sendUploadConfirmation'])->name('send-upload-confirmation');
    Route::post('/{uid}/send-missing', [DocumentValidationController::class, 'sendMissingDocuments'])->name('send-missing');
    Route::post('/{uid}/request-initial-documents', [DocumentValidationController::class, 'requestInitialDocuments'])->name('request-initial');
    Route::post('/{uid}/request-missing-documents', [DocumentValidationController::class, 'requestMissingDocuments'])->name('request-missing');

    // Document notes
    Route::post('/{uid}/notes', [DocumentValidationController::class, 'addNote'])->name('notes.add');
    Route::put('/{uid}/notes/{noteId}', [DocumentValidationController::class, 'updateNote'])->name('notes.update');
    Route::delete('/{uid}/notes/{noteId}', [DocumentValidationController::class, 'deleteNote'])->name('notes.delete');

    // File operations
    Route::post('/sync/fields', [DocumentValidationController::class, 'syncAllDocumentFields'])->name('sync-fields');
    Route::post('/{uid}/admin-upload', [DocumentValidationController::class, 'uploadDocument'])->name('upload');
    Route::post('/{uid}/attachments', [DocumentValidationController::class, 'uploadAttachment'])->name('attachments.upload');
    Route::post('/{uid}/upload-attachment', [DocumentValidationController::class, 'uploadAdditionalAttachment'])->name('upload-attachment');
    Route::delete('/attachments/{attachmentId}', [DocumentValidationController::class, 'deleteAttachment'])->name('attachments.delete');
    Route::delete('/{uid}/delete-attachment/{mediaId}', [DocumentValidationController::class, 'deleteAdditionalAttachment'])->name('delete-attachment');

    // History & timeline
    Route::get('/{uid}/action-history', [DocumentValidationController::class, 'getActionHistory'])->name('action-history');
    Route::get('/{uid}/email-history', [DocumentValidationController::class, 'getEmailHistory'])->name('email-history');
    Route::get('/{uid}/emails', [DocumentValidationController::class, 'emailHistory'])->name('emails');
    Route::get('/{uid}/emails/{mailUid}/preview', [DocumentValidationController::class, 'emailPreview'])->name('emails.preview.api');
    Route::get('/{uid}/status-timeline', [DocumentValidationController::class, 'getStatusTimeline'])->name('status-timeline');
    Route::get('/{uid}/attachments', [DocumentValidationController::class, 'getAdditionalAttachments'])->name('attachments');

    // Dynamic data for modals
    Route::get('/{uid}/next-stage-info', [DocumentValidationController::class, 'getNextStageInfo'])->name('next-stage-info');
    Route::get('/custom-email-template', [DocumentValidationController::class, 'getCustomEmailTemplate'])->name('custom-email-template');

    // Document operations (moved from role-specific routes)
    Route::post('/update', [DocumentsController::class, 'update'])->name('update');
    Route::post('/sync-fields', [DocumentsController::class, 'syncAllDocumentFields'])->name('sync-fields.documents');
    Route::get('/{uid}/state', [DocumentsController::class, 'getDocumentState'])->name('state');
    Route::get('/destroy/{uid}', [DocumentsController::class, 'destroy'])->name('destroy');
    Route::delete('/{uid}', [DocumentsController::class, 'deleteSingleDocument'])->name('delete');

    // Refresh operations
    Route::get('/{uid}/refresh-section', [DocumentsController::class, 'refreshDocumentsSection'])->name('refresh-section');
    Route::get('/{uid}/refresh-management', [DocumentsController::class, 'refreshManagementSection'])->name('refresh-management');
    Route::get('/{uid}/refresh-action-history', [DocumentsController::class, 'refreshActionHistory'])->name('refresh-action-history');

    // File operations (moved from role-specific routes)
    // ✅ IMPORTANTE: La ruta POST /{uid}/files está definida en RouteServiceProvider como pública (uploadFiles)
    // No la incluimos aquí para evitar conflictos de middleware
    Route::get('/files/{id}', [DocumentsController::class, 'getFiles'])->name('files.get');
    Route::delete('/files/{id}', [DocumentsController::class, 'deleteFiles'])->name('files.delete');
});

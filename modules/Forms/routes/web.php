<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\Api\FormApiController;
use Modules\Forms\Http\Controllers\FormAccessTokenController;
use Modules\Forms\Http\Controllers\FormCategoryController;
use Modules\Forms\Http\Controllers\FormController;
use Modules\Forms\Http\Controllers\FormFieldController;
use Modules\Forms\Http\Controllers\FormFieldEditorController;
use Modules\Forms\Http\Controllers\FormFieldTypeSettingController;
use Modules\Forms\Http\Controllers\FormFollowUpController;
use Modules\Forms\Http\Controllers\FormPublicController;
use Modules\Forms\Http\Controllers\FormsDashboardController;
use Modules\Forms\Http\Controllers\FormSettingsController;
use Modules\Forms\Http\Controllers\FormsInboxController;
use Modules\Forms\Http\Controllers\FormSubmissionController;
use Modules\Forms\Http\Controllers\FormSubmissionEmailController;
use Modules\Forms\Http\Controllers\FormTemplateLibraryController;
use Modules\Forms\Http\Controllers\FormVersionController;

// ─── Rutas inbox (antes del catch-all público forms/{slug}) ──────────────────

Route::middleware(['web', 'auth'])
    ->prefix('panel/forms/inbox')
    ->name('forms.inbox.')
    ->group(function () {
        Route::get('/', [FormsInboxController::class, 'index'])->name('index');
        Route::post('/bulk-action', [FormsInboxController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/dashboard', [FormsDashboardController::class, 'index'])->name('dashboard');
        Route::get('/{submission}/emails', [FormSubmissionEmailController::class, 'index'])->name('emails.index');
        Route::get('/{submission}/emails/{email}/preview', [FormSubmissionEmailController::class, 'preview'])->name('emails.preview');
        Route::post('/{submission}/emails/send-custom', [FormSubmissionEmailController::class, 'sendCustom'])->name('emails.send-custom');
        Route::post('/{submission}/emails/{email}/resend', [FormSubmissionEmailController::class, 'resend'])->name('emails.resend');
        Route::post('/{submission}/emails/send-confirmation', [FormSubmissionEmailController::class, 'sendConfirmation'])->name('emails.send-confirmation');
        Route::post('/{submission}/files', [FormsInboxController::class, 'uploadFile'])->name('files.upload');
        Route::delete('/{submission}/files/{file}', [FormsInboxController::class, 'deleteFile'])->name('files.delete');
        Route::get('/{submission}', [FormsInboxController::class, 'show'])->name('show');
        Route::get('/{submission}/manage', [FormsInboxController::class, 'manage'])->name('manage');
        Route::post('/{submission}/manage', [FormsInboxController::class, 'updateManage'])->name('manage.update');
    });

// ─── Rutas públicas ──────────────────────────────────────────────────────────

Route::middleware(['web'])
    ->prefix('forms')
    ->name('forms.public.')
    ->group(function () {
        Route::post('/{slug}/submit', [FormPublicController::class, 'submit'])->name('submit');
        Route::get('/embed/{slug}', [FormPublicController::class, 'embed'])->name('embed');
        Route::get('/access/{token}', [FormPublicController::class, 'accessByToken'])->name('access');
        Route::post('/abandon/{slug}', [FormPublicController::class, 'trackAbandon'])->name('abandon');
        Route::get('/abandon/{slug}/restore', [FormPublicController::class, 'restoreAbandoned'])->name('restore');
        Route::get('/{slug}', [FormPublicController::class, 'show'])->name('show');
    });

// Preview público con token HMAC — sin throttle restrictivo (token es la protección)
Route::middleware(['web'])
    ->get('forms/preview/{form}/{token}', [FormController::class, 'previewPublic'])
    ->name('forms.public.preview.public');

// ─── Visual editor (sin middleware settings) ──────────────────────────────────

Route::middleware(['web', 'auth'])
    ->patch('panel/forms/fields/{field}/editor', [FormFieldEditorController::class, 'update'])
    ->name('forms.fields.editor.update');

Route::middleware(['web', 'auth'])
    ->prefix('panel/forms/internal')
    ->name('forms.internal.')
    ->group(function () {
        Route::get('/picker', [FormApiController::class, 'picker'])->name('picker');
        Route::get('/{form}/meta', [FormApiController::class, 'meta'])->name('meta');
        Route::get('/{form}/preview-html', [FormApiController::class, 'previewHtml'])->name('preview-html');
        Route::post('/quick-store', [FormApiController::class, 'quickStore'])->name('quick-store');
    });

// ─── Rutas admin ─────────────────────────────────────────────────────────────

Route::middleware(['web', 'auth'])->group(function () {

    Route::prefix('panel/settings/forms')
        ->name('settings.forms.')
        ->middleware(['settings'])
        ->group(function () {

            // Rutas estáticas (deben ir antes de los wildcards /{form})

            // Submissions export (sin {form})
            Route::get('/submissions/export-download', [FormSubmissionController::class, 'exportDownload'])->name('submissions.export-download');

            // Import
            Route::post('/import-json', [FormController::class, 'importJson'])->name('import-json');

            // Field type settings
            Route::prefix('/field-types')->name('field-types.')->group(function () {
                Route::get('/', [FormFieldTypeSettingController::class, 'index'])->name('index');
                Route::post('/bulk-action', [FormFieldTypeSettingController::class, 'bulkAction'])->name('bulk-action');
                Route::post('/reorder', [FormFieldTypeSettingController::class, 'reorder'])->name('reorder');
                Route::get('/{typeSetting}/edit', [FormFieldTypeSettingController::class, 'edit'])->name('edit');
                Route::put('/{typeSetting}', [FormFieldTypeSettingController::class, 'updateFull'])->name('update-full');
                Route::patch('/{typeSetting}', [FormFieldTypeSettingController::class, 'update'])->name('update');
                Route::patch('/{typeSetting}/toggle', [FormFieldTypeSettingController::class, 'toggle'])->name('toggle');
            });

            // Categorias
            Route::prefix('/categories')->name('categories.')->group(function () {
                Route::post('/bulk-action', [FormCategoryController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/', [FormCategoryController::class, 'index'])->name('index');
                Route::get('/create', [FormCategoryController::class, 'create'])->name('create');
                Route::post('/', [FormCategoryController::class, 'store'])->name('store');
                Route::get('/{category}/edit', [FormCategoryController::class, 'edit'])->name('edit');
                Route::patch('/{category}', [FormCategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [FormCategoryController::class, 'destroy'])->name('destroy');
                Route::patch('/{category}/toggle', [FormCategoryController::class, 'toggle'])->name('toggle');
                Route::post('/reorder', [FormCategoryController::class, 'reorder'])->name('reorder');
            });

            // Biblioteca de plantillas
            Route::prefix('/templates')->name('templates-library.')->group(function () {
                Route::get('/', [FormTemplateLibraryController::class, 'index'])->name('index');
                Route::post('/{template}/use', [FormTemplateLibraryController::class, 'use'])->name('use');
                Route::get('/{template}/preview', [FormTemplateLibraryController::class, 'preview'])->name('preview');
            });

            // CRUD principal
            Route::post('/bulk-action', [FormController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/', [FormController::class, 'index'])->name('index');
            Route::get('/create', [FormController::class, 'create'])->name('create');
            Route::post('/', [FormController::class, 'store'])->name('store');
            Route::get('/{form}', [FormController::class, 'show'])->name('show');
            Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit');
            Route::patch('/{form}', [FormController::class, 'update'])->name('update');
            Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');

            // Acciones especiales del formulario
            Route::post('/{form}/clone', [FormController::class, 'clone'])->name('clone');
            Route::get('/{form}/preview', [FormController::class, 'preview'])->name('preview');
            Route::get('/{form}/qrcode', [FormController::class, 'qrcode'])->name('qrcode');
            Route::get('/{form}/export-json', [FormController::class, 'exportJson'])->name('export-json');
            Route::get('/{form}/analytics', [FormController::class, 'analytics'])->name('analytics');
            Route::get('/{form}/html-source', [FormController::class, 'htmlSource'])->name('html-source');

            // Configuracion del formulario (tabs)
            Route::prefix('/{form}/settings')->name('settings.')->group(function () {
                Route::get('/', [FormSettingsController::class, 'edit'])->name('edit');
                Route::patch('/', [FormSettingsController::class, 'update'])->name('update');
                Route::get('/emails', [FormSettingsController::class, 'emails'])->name('emails');
                Route::patch('/emails', [FormSettingsController::class, 'updateEmails'])->name('emails.update');
                Route::get('/seo', [FormSettingsController::class, 'seo'])->name('seo');
                Route::patch('/seo', [FormSettingsController::class, 'updateSeo'])->name('seo.update');
                Route::get('/access', [FormSettingsController::class, 'access'])->name('access');
                Route::patch('/access', [FormSettingsController::class, 'updateAccess'])->name('access.update');
                Route::get('/gdpr', [FormSettingsController::class, 'gdpr'])->name('gdpr');
                Route::patch('/gdpr', [FormSettingsController::class, 'updateGdpr'])->name('gdpr.update');
            });

            // Campos (AJAX)
            Route::prefix('/{form}/fields')->name('fields.')->group(function () {
                Route::get('/', [FormFieldController::class, 'index'])->name('index');
                Route::post('/', [FormFieldController::class, 'store'])->name('store');
                Route::get('/{field}/edit', [FormFieldController::class, 'edit'])->name('edit');
                Route::patch('/{field}', [FormFieldController::class, 'update'])->name('update');
                Route::delete('/{field}', [FormFieldController::class, 'destroy'])->name('destroy');
                Route::post('/reorder', [FormFieldController::class, 'reorder'])->name('reorder');
                Route::post('/{field}/duplicate', [FormFieldController::class, 'duplicate'])->name('duplicate');
                Route::post('/{field}/translate', [FormFieldController::class, 'translateWithDeepL'])->name('translate');
            });

            // Submissions
            Route::prefix('/{form}/submissions')->name('submissions.')->group(function () {
                Route::get('/', [FormSubmissionController::class, 'index'])->name('index');
                Route::get('/{submission}', [FormSubmissionController::class, 'show'])->name('show');
                Route::delete('/{submission}', [FormSubmissionController::class, 'destroy'])->name('destroy');
                Route::post('/export', [FormSubmissionController::class, 'export'])->name('export');
                Route::post('/bulk-delete', [FormSubmissionController::class, 'bulkDelete'])->name('bulk-delete');
                Route::patch('/{submission}/status', [FormSubmissionController::class, 'updateStatus'])->name('status');
                Route::patch('/{submission}/toggle-spam', [FormSubmissionController::class, 'toggleSpam'])->name('toggle-spam');
                Route::patch('/{submission}/toggle-star', [FormSubmissionController::class, 'toggleStar'])->name('toggle-star');
                Route::patch('/{submission}/assign', [FormSubmissionController::class, 'assign'])->name('assign');
                Route::post('/{submission}/notes', [FormSubmissionController::class, 'addNote'])->name('notes.add');
                Route::delete('/{submission}/notes/{note}', [FormSubmissionController::class, 'deleteNote'])->name('notes.delete');
                Route::post('/{submission}/resend-email', [FormSubmissionController::class, 'resendEmail'])->name('resend-email');
                Route::get('/{submission}/pdf', [FormSubmissionController::class, 'exportPdf'])->name('pdf');
                Route::post('/anonymize', [FormSubmissionController::class, 'bulkAnonymize'])->name('anonymize');
            });

            // Pasos (multi-step)
            Route::post('/{form}/steps', [FormController::class, 'addStep'])->name('steps.add');
            Route::delete('/{form}/steps', [FormController::class, 'removeStep'])->name('steps.remove');

            // Protección anti-spam (AJAX toggle)
            Route::patch('/{form}/protection', [FormController::class, 'updateProtection'])->name('protection');

            // Versiones
            Route::prefix('/{form}/versions')->name('versions.')->group(function () {
                Route::get('/', [FormVersionController::class, 'index'])->name('index');
                Route::post('/{version}/restore', [FormVersionController::class, 'restore'])->name('restore');
            });

            // Follow-ups
            Route::prefix('/{form}/follow-ups')->name('follow-ups.')->group(function () {
                Route::get('/', [FormFollowUpController::class, 'index'])->name('index');
                Route::post('/', [FormFollowUpController::class, 'store'])->name('store');
                Route::patch('/{followUp}', [FormFollowUpController::class, 'update'])->name('update');
                Route::delete('/{followUp}', [FormFollowUpController::class, 'destroy'])->name('destroy');
            });

            // Tokens de acceso
            Route::prefix('/{form}/access-tokens')->name('access-tokens.')->group(function () {
                Route::get('/', [FormAccessTokenController::class, 'index'])->name('index');
                Route::post('/', [FormAccessTokenController::class, 'store'])->name('store');
                Route::delete('/{token}', [FormAccessTokenController::class, 'destroy'])->name('destroy');
            });
        });
});

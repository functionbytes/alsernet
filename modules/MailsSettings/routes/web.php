<?php

use Illuminate\Support\Facades\Route;
use Modules\MailsSettings\Http\Controllers\EmailSettingsController;
use Modules\MailsSettings\Http\Controllers\IncomingEmailSettingsController;
use Modules\MailsSettings\Http\Controllers\OutgoingEmailSettingsController;

/*
|--------------------------------------------------------------------------
| Email Settings Routes
|--------------------------------------------------------------------------
|
| Rutas para la configuración de email del sistema
| Middleware: web, auth, settings
|
*/

// Email settings routes
Route::middleware(['web', 'auth', 'settings'])
    ->prefix('panel/settings/email')
    ->name('settings.email.')
    ->group(function () {
        Route::get('/', [EmailSettingsController::class, 'index'])->name('index');
        Route::post('/update', [EmailSettingsController::class, 'update'])->name('update');
    });

// Incoming email settings routes
Route::middleware(['web', 'auth', 'settings'])
    ->prefix('panel/settings/incoming-email')
    ->name('settings.incoming-email.')
    ->group(function () {
        Route::get('/', [IncomingEmailSettingsController::class, 'index'])->name('index');
        Route::post('/update', [IncomingEmailSettingsController::class, 'update'])->name('update');

        // IMAP connections
        Route::post('/imap/store', [IncomingEmailSettingsController::class, 'storeImapConnection'])->name('imap.store');
        Route::delete('/imap/{id}', [IncomingEmailSettingsController::class, 'deleteImapConnection'])->name('imap.delete');
        Route::post('/imap/test', [IncomingEmailSettingsController::class, 'testImapConnection'])->name('imap.test');

        // Gmail connections
        Route::post('/gmail/update', [IncomingEmailSettingsController::class, 'updateGmail'])->name('gmail.update');
        Route::get('/gmail/authorize', [IncomingEmailSettingsController::class, 'gmailAuthorize'])->name('gmail.authorize');
        Route::get('/gmail/callback', [IncomingEmailSettingsController::class, 'gmailCallback'])->name('gmail.callback');
        Route::delete('/gmail/{id}', [IncomingEmailSettingsController::class, 'deleteGmailConnection'])->name('gmail.delete');

        // Other handlers
        Route::post('/pipe/update', [IncomingEmailSettingsController::class, 'updatePipe'])->name('pipe.update');
        Route::post('/api/update', [IncomingEmailSettingsController::class, 'updateApi'])->name('api.update');
        Route::post('/api/generate-key', [IncomingEmailSettingsController::class, 'generateApiKey'])->name('api.generate-key');
        Route::get('/api/documentation', [IncomingEmailSettingsController::class, 'apiDocumentation'])->name('api.documentation');
        Route::post('/mailgun/update', [IncomingEmailSettingsController::class, 'updateMailgun'])->name('mailgun.update');

        // phpList integration
        Route::post('/phplist/update', [IncomingEmailSettingsController::class, 'updatePhplist'])->name('phplist.update');
        Route::post('/phplist/test', [IncomingEmailSettingsController::class, 'testPhplistConnection'])->name('phplist.test');
        Route::get('/phplist/lists', [IncomingEmailSettingsController::class, 'getPhplistLists'])->name('phplist.lists');
        Route::post('/phplist/subscribe', [IncomingEmailSettingsController::class, 'phplistSubscribe'])->name('phplist.subscribe');
    });

// Outgoing email settings routes
Route::middleware(['web', 'auth', 'settings'])
    ->prefix('panel/settings/outgoing-email')
    ->name('settings.outgoing-email.')
    ->group(function () {
        Route::get('/', [OutgoingEmailSettingsController::class, 'index'])->name('index');
        Route::get('/edit', [OutgoingEmailSettingsController::class, 'edit'])->name('edit');
        Route::post('/update', [OutgoingEmailSettingsController::class, 'update'])->name('update');
        Route::post('/test-connection', [OutgoingEmailSettingsController::class, 'testConnection'])->name('test-connection');
        Route::post('/send-test', [OutgoingEmailSettingsController::class, 'sendTestEmail'])->name('send-test');
    });

// Legacy redirects: panel/setting/{email,incoming-email,outgoing-email} → panel/settings/...
Route::middleware(['web'])->group(function () {
    Route::redirect('panel/setting/email/{any}', 'panel/settings/email/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/email', 'panel/settings/email', 301);
    Route::redirect('panel/setting/incoming-email/{any}', 'panel/settings/incoming-email/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/incoming-email', 'panel/settings/incoming-email', 301);
    Route::redirect('panel/setting/outgoing-email/{any}', 'panel/settings/outgoing-email/{any}', 301)->where('any', '.*');
    Route::redirect('panel/setting/outgoing-email', 'panel/settings/outgoing-email', 301);
});

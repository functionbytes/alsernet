<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Http\Controllers\MailerComponentController;
use Modules\Mailer\Http\Controllers\MailerEndpointController;
use Modules\Mailer\Http\Controllers\MailerTemplateController;
use Modules\Mailer\Http\Controllers\MailerVariableController;

/*
|--------------------------------------------------------------------------
| Mailer Admin Routes
|--------------------------------------------------------------------------
|
| Rutas para la gestión de configuraciones de email (panel administrativo)
| Incluye: GET (vistas), POST/PATCH/DELETE (acciones)
|
*/

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('mailers')
    ->name('mailers.')
    ->group(function () {
        // ====================================================================
        // TEMPLATES
        // ====================================================================
        Route::prefix('templates')->name('templates.')->group(function () {
            // Views - GET
            Route::get('/', [MailerTemplateController::class, 'index'])->name('index');
            Route::get('/create', [MailerTemplateController::class, 'create'])->name('create');
            Route::get('/edit/{uid}/{translation_uid?}', [MailerTemplateController::class, 'edit'])->name('edit');
            Route::get('/preview/{uid}', [MailerTemplateController::class, 'preview'])->name('preview');
            Route::get('/preview-ajax/{uid}', [MailerTemplateController::class, 'previewAjax'])->name('preview-ajax');
            Route::get('/variables/{uid}', [MailerTemplateController::class, 'getVariables'])->name('variables');
            Route::get('/variables-by-module', [MailerTemplateController::class, 'variablesByModule'])->name('variables-by-module');

            // Actions - POST, PATCH, DELETE
            Route::post('/', [MailerTemplateController::class, 'store'])->name('store');
            Route::patch('/{uid}', [MailerTemplateController::class, 'update'])->name('update');
            Route::delete('/{uid}', [MailerTemplateController::class, 'destroy'])->name('destroy');
            Route::post('/toggle-status/{uid}', [MailerTemplateController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/send-test/{uid}', [MailerTemplateController::class, 'sendTest'])->name('send-test');
            Route::post('/format-html', [MailerTemplateController::class, 'formatHtml'])->name('format-html');
        });

        // ====================================================================
        // COMPONENTS
        // ====================================================================
        Route::prefix('components')->name('components.')->group(function () {
            // Views - GET
            Route::get('/', [MailerComponentController::class, 'index'])->name('index');
            Route::get('/create', [MailerComponentController::class, 'create'])->name('create');
            Route::get('/edit/{uid}/{translation_uid?}', [MailerComponentController::class, 'edit'])->name('edit');
            Route::get('/preview/{uid}', [MailerComponentController::class, 'preview'])->name('preview');
            Route::get('/preview-ajax/{uid}', [MailerComponentController::class, 'previewAjax'])->name('preview-ajax');
            Route::get('/variables', [MailerComponentController::class, 'variables'])->name('variables');

            // Actions - POST, PATCH, DELETE
            Route::post('/', [MailerComponentController::class, 'store'])->name('store');
            Route::patch('/{uid}', [MailerComponentController::class, 'update'])->name('update');
            Route::delete('/{uid}', [MailerComponentController::class, 'destroy'])->name('destroy');
            Route::post('/duplicate/{uid}', [MailerComponentController::class, 'duplicate'])->name('duplicate');
        });

        // ====================================================================
        // VARIABLES
        // ====================================================================
        Route::prefix('variables')->name('variables.')->group(function () {
            // Views - GET
            Route::get('/', [MailerVariableController::class, 'index'])->name('index');
            Route::get('/create', [MailerVariableController::class, 'create'])->name('create');
            Route::get('/edit/{variable}', [MailerVariableController::class, 'edit'])->name('edit');
            Route::get('/by-module', [MailerVariableController::class, 'getByModule'])->name('by-module');

            // Actions - POST, PATCH, DELETE
            Route::post('/', [MailerVariableController::class, 'store'])->name('store');
            Route::patch('/{variable}', [MailerVariableController::class, 'update'])->name('update');
            Route::delete('/{variable}', [MailerVariableController::class, 'destroy'])->name('destroy');
            Route::post('/toggle-status/{variable}', [MailerVariableController::class, 'toggleStatus'])->name('toggle-status');
        });

        // ====================================================================
        // ENDPOINTS
        // ====================================================================
        Route::prefix('endpoints')->name('endpoints.')->group(function () {
            // Views - GET
            Route::get('/documentation', [MailerEndpointController::class, 'documentation'])->name('documentation');
            Route::get('/', [MailerEndpointController::class, 'index'])->name('index');
            Route::get('/create', [MailerEndpointController::class, 'create'])->name('create');
            Route::get('/edit/{emailEndpoint}', [MailerEndpointController::class, 'edit'])->name('edit');
            Route::get('/logs/{emailEndpoint}', [MailerEndpointController::class, 'logs'])->name('logs');

            // Actions - POST, PATCH, DELETE
            Route::post('/', [MailerEndpointController::class, 'store'])->name('store');
            Route::patch('/{emailEndpoint}', [MailerEndpointController::class, 'update'])->name('update');
            Route::delete('/{emailEndpoint}', [MailerEndpointController::class, 'destroy'])->name('destroy');
            Route::post('/regenerate-token/{emailEndpoint}', [MailerEndpointController::class, 'regenerateToken'])->name('regenerate-token');
        });

    });

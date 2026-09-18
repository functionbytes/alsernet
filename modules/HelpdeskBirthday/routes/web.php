<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskBirthday\Http\Controllers\Managers\BirthdayCampaignsController;
use Modules\HelpdeskBirthday\Http\Controllers\Settings\BirthdaySettingsController;

/*
| Rutas del panel de HelpdeskBirthday.
|
| Las monta HelpdeskBirthdayServiceProvider con prefijo 'panel/helpdeskbirthday'
| y middleware ['web', 'auth']. {campaign} resuelve BirthdayCampaign.
|
| Las acciones son POST y no PUT/PATCH a propósito: en este entorno Docker un
| PUT real por AJAX devuelve 405 aunque route:list lo muestre.
*/
Route::name('helpdeskbirthday.')
    ->middleware(['can:helpdeskbirthday.view', 'integration.enabled:birthday'])
    ->group(function () {
        Route::get('campaigns', [BirthdayCampaignsController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/{campaign}', [BirthdayCampaignsController::class, 'show'])->name('campaigns.show');
        Route::get('campaigns/{campaign}/preview', [BirthdayCampaignsController::class, 'preview'])->name('campaigns.preview');
        // Los destinatarios ya no son la portada de la campaña: viven en su
        // pestaña y {campaign} abre el cuadro de mando.
        Route::get('campaigns/{campaign}/recipients', [BirthdayCampaignsController::class, 'recipients'])->name('campaigns.recipients');
        Route::get('campaigns/{campaign}/redemptions', [BirthdayCampaignsController::class, 'redemptions'])->name('campaigns.redemptions');
        Route::get('campaigns/{campaign}/reconciliation', [BirthdayCampaignsController::class, 'reconciliation'])->name('campaigns.reconciliation');

        // El correo concreto que recibió un destinatario, para verlo desde su
        // fila. Se sirve como documento HTML aparte (lo carga un iframe) y no
        // embebido en el panel: el CSS del correo no debe pisar el del panel.
        Route::get('campaigns/{campaign}/recipients/{recipient}/email', [BirthdayCampaignsController::class, 'recipientEmail'])
            ->name('campaigns.recipient-email');

        Route::middleware('can:helpdeskbirthday.manage')->group(function () {
            Route::post('campaigns/prepare', [BirthdayCampaignsController::class, 'prepare'])->name('campaigns.prepare');
            Route::post('campaigns/{campaign}/pause', [BirthdayCampaignsController::class, 'pause'])->name('campaigns.pause');
            Route::post('campaigns/{campaign}/resume', [BirthdayCampaignsController::class, 'resume'])->name('campaigns.resume');
            Route::post('campaigns/{campaign}/cancel', [BirthdayCampaignsController::class, 'cancel'])->name('campaigns.cancel');

            Route::post('campaigns/{campaign}/recipients/{recipient}/unsubscribe', [BirthdayCampaignsController::class, 'unsubscribeRecipient'])
                ->name('campaigns.recipient-unsubscribe');
            Route::post('campaigns/{campaign}/recipients/{recipient}/retry', [BirthdayCampaignsController::class, 'retryRecipient'])
                ->name('campaigns.recipient-retry');
            Route::post('campaigns/{campaign}/retry-failed', [BirthdayCampaignsController::class, 'retryFailed'])
                ->name('campaigns.retry-failed');

            // Pide a gestión los bonos que faltan. Habla con el ERP y puede
            // tardar (una llamada por cada 100 clientes): throttle bajo.
            Route::post('campaigns/{campaign}/retry-bonos', [BirthdayCampaignsController::class, 'retryBonos'])
                ->middleware('throttle:10,1')
                ->name('campaigns.retry-bonos');

            // Habla con PrestaShop: throttle bajo para que el botón de
            // «actualizar» no se convierta en una forma de martillear la tienda.
            Route::post('campaigns/{campaign}/sync-redemptions', [BirthdayCampaignsController::class, 'syncRedemptions'])
                ->middleware('throttle:10,1')
                ->name('campaigns.sync-redemptions');

            // Marca en el ERP, en lote, los bonos que la tienda descontó y
            // gestión nunca registró. ESCRIBE EN EL ERP: throttle muy bajo.
            Route::post('campaigns/{campaign}/reconcile', [BirthdayCampaignsController::class, 'reconcile'])
                ->middleware('throttle:5,1')
                ->name('campaigns.reconcile');

            // Escribe en el ERP (marca el bono como consumido): throttle bajo.
            Route::post('campaigns/{campaign}/mark-coupon-used', [BirthdayCampaignsController::class, 'markCouponUsed'])
                ->middleware('throttle:20,1')
                ->name('campaigns.mark-coupon-used');
        });

        Route::get('settings', [BirthdaySettingsController::class, 'index'])
            ->middleware('can:helpdeskbirthday.settings.view')
            ->name('settings.index');
        Route::post('settings', [BirthdaySettingsController::class, 'update'])
            ->middleware('can:helpdeskbirthday.settings.update')
            ->name('settings.update');
        // Envío de prueba a direcciones internas. Throttle porque manda correo
        // de verdad: no queremos que se convierta en un relay improvisado.
        Route::post('settings/test-send', [BirthdaySettingsController::class, 'testSend'])
            ->middleware(['can:helpdeskbirthday.manage', 'throttle:10,1'])
            ->name('settings.test-send');
    });

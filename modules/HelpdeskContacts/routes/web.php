<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskContacts\Http\Controllers\Managers\ContactCartController;
use Modules\HelpdeskContacts\Http\Controllers\Managers\ContactsController;
use Modules\HelpdeskContacts\Http\Controllers\Managers\ContactsMergeController;
use Modules\HelpdeskContacts\Http\Controllers\Managers\ContactTabsController;

/*
| Contactos CRM 360.
|
| Mounted by HelpdeskContactsServiceProvider with prefix 'panel/contacts'
| and middleware ['web', 'auth']. The {customer} parameter is the
| helpdesk_customers id; implicit binding resolves Modules\Helpdesk\Models\Customer
| on the 'helpdesk' connection.
*/
Route::name('contacts.')
    ->middleware('can:contacts.view')
    ->group(function () {
        Route::get('/', [ContactsController::class, 'index'])->name('index');

        // Exportar contactos a CSV (aplica los mismos filtros que index)
        Route::get('/export', [ContactsController::class, 'export'])
            ->name('export');

        // Importar contactos desde CSV
        Route::get('/import', [ContactsController::class, 'importForm'])
            ->middleware('can:contacts.update')
            ->name('import');

        Route::post('/import', [ContactsController::class, 'importProcess'])
            ->middleware('can:contacts.update')
            ->name('import.process');

        // Reportes y dashboard at-risk — static paths before {customer} wildcard
        Route::get('/reports', [ContactsController::class, 'reports'])
            ->name('reports');

        // Bulk actions sobre múltiples contactos
        Route::post('/bulk-action', [ContactsController::class, 'bulkAction'])
            ->middleware('can:contacts.update')
            ->name('bulk-action');

        // Plantillas HSM aprobadas para el modal de envío WhatsApp
        Route::get('/hsm-templates', [ContactsController::class, 'hsmTemplates'])
            ->name('hsm-templates');

        // Envío masivo de plantilla HSM a varios contactos seleccionados
        Route::post('/bulk-send-hsm', [ContactsController::class, 'bulkSendHsm'])
            ->middleware('can:contacts.update')
            ->name('bulk-send-hsm');

        // Búsqueda ERP/PrestaShop → crear ficha nueva o vincular a un contacto
        // existente. Llama a CustomerIntegrationService directo (HelpdeskIntegration)
        // en vez de sus rutas propias, que exigen can:helpdesk.view — un agente
        // de solo-contactos no lo tendría (mismo criterio que hsm-templates).
        Route::get('/external-platforms', [ContactsController::class, 'externalPlatforms'])
            ->name('external-platforms');

        Route::get('/external-search', [ContactsController::class, 'externalSearch'])
            ->name('external-search');

        // Ficha completa (direccion, pedidos, facturas) de un resultado de
        // búsqueda antes de decidir importarlo — keyed por email, sin crear nada.
        Route::get('/external-preview', [ContactsController::class, 'externalPreview'])
            ->name('external-preview');

        Route::post('/external-create', [ContactsController::class, 'externalCreate'])
            ->middleware('can:contacts.update')
            ->name('external-create');

        // Actualizar datos del contacto
        Route::put('/{customer}', [ContactsController::class, 'update'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('update');

        // Eliminar contacto individual (mismo permiso que el borrado masivo)
        Route::delete('/{customer}', [ContactsController::class, 'destroy'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('destroy');

        // Merge de contactos duplicados
        Route::prefix('/{customer}/merge')
            ->whereNumber('customer')
            ->middleware('can:contacts.merge')
            ->group(function () {
                Route::get('/search', [ContactsMergeController::class, 'search'])->name('merge.search');
                Route::get('/preview', [ContactsMergeController::class, 'preview'])->name('merge.preview');
                // Merge es destructivo sobre PII (soft-delete + reasignación):
                // queda trazado en el log de auditoría igual que las acciones
                // sensibles de HelpdeskErp.
                Route::post('/', [ContactsMergeController::class, 'execute'])
                    ->middleware('audit.access:contacts,merge')
                    ->name('merge.execute');
            });

        Route::get('/{customer}', [ContactsController::class, 'show'])
            ->whereNumber('customer')
            ->name('show');

        Route::prefix('/{customer}/tab')
            ->whereNumber('customer')
            ->group(function () {
                Route::get('/resumen', [ContactTabsController::class, 'resumen'])->name('tab.resumen');
                Route::get('/conversaciones', [ContactTabsController::class, 'conversaciones'])->name('tab.conversaciones');
                Route::get('/chats', [ContactTabsController::class, 'chats'])->name('tab.chats');
                Route::get('/erp', [ContactTabsController::class, 'erp'])->name('tab.erp');
                Route::get('/prestashop', [ContactTabsController::class, 'prestashop'])->name('tab.prestashop');
                Route::get('/tienda', [ContactTabsController::class, 'tienda'])->name('tab.tienda');
                Route::get('/actividad', [ContactTabsController::class, 'actividad'])->name('tab.actividad');
                Route::get('/tickets', [ContactTabsController::class, 'tickets'])->name('tab.tickets');
            });

        // Ban / Unban
        Route::post('/{customer}/ban', [ContactsController::class, 'ban'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('ban');

        Route::post('/{customer}/unban', [ContactsController::class, 'unban'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('unban');

        // Enviar plantilla HSM de WhatsApp (crea o reusa la conversación del contacto)
        Route::post('/{customer}/send-hsm', [ContactsController::class, 'sendHsm'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('send-hsm');

        // Vincular un resultado de búsqueda ERP/PrestaShop a este contacto ("unirla")
        Route::post('/{customer}/external-link', [ContactsController::class, 'externalLink'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('external-link');

        // Plataformas ya vinculadas, para la sección "Integraciones" de la ficha 360
        Route::get('/{customer}/external-integrations', [ContactsController::class, 'externalIntegrations'])
            ->whereNumber('customer')
            ->name('external-integrations');

        Route::post('/{customer}/sync', [ContactTabsController::class, 'sync'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('sync');

        // Crear ticket para el contacto (vía bridge de HelpdeskTickets).
        Route::post('/{customer}/tickets', [ContactTabsController::class, 'createTicket'])
            ->whereNumber('customer')
            ->middleware('can:contacts.update')
            ->name('tickets.store');

        /*
        | Proxy del carrito asistido bajo el gate contacts.*.
        |
        | Las rutas nativas manager.helpdesk.customers.cart.* autorizan contra
        | la CustomerPolicy (helpdesk.customers.*), permiso que un agente de
        | contactos no necesariamente tiene. Estas rutas re-gatean en
        | contacts.view / contacts.update y reenvían a AssistedCartService.
        */
        Route::prefix('/{customer}/cart')
            ->whereNumber('customer')
            ->name('cart.')
            ->group(function () {
                Route::get('/', [ContactCartController::class, 'show'])->name('show');
                Route::post('/items', [ContactCartController::class, 'addItem'])
                    ->middleware('can:contacts.update')->name('items.store');
                Route::delete('/items/{item}', [ContactCartController::class, 'removeItem'])
                    ->whereNumber('item')
                    ->middleware('can:contacts.update')->name('items.destroy');
                Route::post('/discount', [ContactCartController::class, 'applyDiscount'])
                    ->middleware('can:contacts.update')->name('discount');
                // Acciones de comercio reales (crear pedido / enviar link de pago):
                // exigen el permiso dedicado contacts.commerce, no el genérico
                // contacts.update de editar contactos/carrito.
                Route::post('/generate-order', [ContactCartController::class, 'generateOrder'])
                    ->middleware('can:contacts.commerce')->name('generate-order');
                Route::post('/send-payment-link', [ContactCartController::class, 'sendPaymentLink'])
                    ->middleware('can:contacts.commerce')->name('send-payment-link');
            });
    });

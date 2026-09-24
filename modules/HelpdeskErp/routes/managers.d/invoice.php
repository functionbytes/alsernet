<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpInvoiceController;

/*
 | Extensión "invoice" de Gestión en el chat — SOLO LECTURA.
 | La incluye el cargador de routes/managers.d con prefijo 'panel/helpdesk' y
 | middleware ['web', 'auth']. {customer} es el id del cliente del helpdesk.
 |
 |  GET customers/{customer}/erp/invoices/{invoiceId}/pdf   copia informativa (PDF)
 |  GET customers/{customer}/erp/invoices/monthly           facturado vs cobrado por mes
 */
Route::prefix('customers/{customer}/erp/invoices')
    ->whereNumber('customer')
    ->name('manager.helpdesk.erp.invoice.')
    ->group(function () {
        Route::get('/monthly', [ErpInvoiceController::class, 'monthly'])
            ->middleware('throttle:60,1')
            ->name('monthly');
        // DomPDF es caro: tope más bajo que las lecturas JSON.
        Route::get('/{invoiceId}/pdf', [ErpInvoiceController::class, 'pdf'])
            ->whereNumber('invoiceId')
            ->middleware('throttle:20,1')
            ->name('pdf');
    });

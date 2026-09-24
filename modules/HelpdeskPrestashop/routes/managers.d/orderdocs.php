<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\OrderdocsController;

// Extensión "orderdocs": notas internas y PDF de documentos del pedido
// (workspace de pedido del inbox). Solo lecturas en la tienda; atadas a
// {customer} para verificar la propiedad del pedido server-side.
Route::get('/customers/{customer}/ps/orders/{order}/orderdocs/notes', [OrderdocsController::class, 'notes'])
    ->whereNumber('order')
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.orderdocs.notes');

Route::get('/customers/{customer}/ps/orders/{order}/orderdocs/documents', [OrderdocsController::class, 'documents'])
    ->whereNumber('order')
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.ps.ext.orderdocs.documents');

// Generar el PDF cuesta más que una lectura normal del bridge: throttle corto.
Route::get('/customers/{customer}/ps/orders/{order}/orderdocs/documents/{type}/{doc}', [OrderdocsController::class, 'download'])
    ->whereNumber('order')
    ->whereIn('type', ['invoice', 'delivery_slip', 'credit_slip'])
    ->whereNumber('doc')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.ext.orderdocs.download');

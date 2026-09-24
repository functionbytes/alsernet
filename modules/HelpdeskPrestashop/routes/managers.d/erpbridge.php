<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext\ErpbridgeController;

// Extensión "erpbridge": pedido y factura de Gestión (ERP) de un pedido
// PrestaShop, en el workspace de pedido del inbox. Solo lecturas (bridge PS,
// API REST de Gestión y manager Oracle); atada a {customer} para verificar
// la propiedad server-side. Cada apertura hace hasta 7 lecturas contra
// Gestión (la respuesta se cachea 2 min): throttle moderado.
Route::get('/customers/{customer}/ps/orders/{order}/erpbridge', [ErpbridgeController::class, 'show'])
    ->whereNumber('order')
    ->middleware(['throttle:30,1', 'audit.access:erp,ps_order_gestion_view'])
    ->name('manager.helpdesk.ps.ext.erpbridge.show');

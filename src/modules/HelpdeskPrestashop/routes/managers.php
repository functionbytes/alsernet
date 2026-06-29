<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\AssistedCartController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\ProductSearchController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsOrderDetailController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsRecommendationController;

// Order detail — usado por el inbox para enriquecer el modal de pedido PS
Route::get('/ps/orders/{order}/detail', PsOrderDetailController::class)
    ->whereNumber('order')
    ->name('manager.helpdesk.ps.orders.detail');

// Carrito asistido (construido por el agente desde el chat) — módulo HelpdeskPrestashop
Route::prefix('customers/{customer}/cart')
    ->name('manager.helpdesk.customers.cart.')
    ->group(function () {
        Route::get('/', [AssistedCartController::class, 'show'])->name('show');
        Route::post('/items', [AssistedCartController::class, 'addItem'])->name('items.store');
        Route::patch('/items/{item}', [AssistedCartController::class, 'updateItem'])->whereNumber('item')->name('items.update');
        Route::delete('/items/{item}', [AssistedCartController::class, 'removeItem'])->whereNumber('item')->name('items.destroy');
        Route::post('/discount', [AssistedCartController::class, 'applyDiscount'])->name('discount');
        Route::post('/clear', [AssistedCartController::class, 'clear'])->name('clear');
        Route::post('/generate-order', [AssistedCartController::class, 'generateOrder'])->name('generate-order');
        Route::post('/send-payment-link', [AssistedCartController::class, 'sendPaymentLink'])->name('send-payment-link');
    });

// Carritos asistidos del cliente — listar y ver detalle
Route::get('/customers/{customer}/carts', [AssistedCartController::class, 'index'])
    ->name('manager.helpdesk.customers.carts.index');
Route::get('/customers/{customer}/carts/{cart}', [AssistedCartController::class, 'showCart'])
    ->whereNumber('cart')
    ->name('manager.helpdesk.customers.carts.show');

// Direcciones PS del cliente
Route::get('/customers/{customer}/ps/addresses', [ProductSearchController::class, 'addresses'])
    ->name('manager.helpdesk.customers.ps.addresses');

// Categorías PS para el filtro de búsqueda
Route::get('/ps/categories', [ProductSearchController::class, 'categories'])
    ->name('manager.helpdesk.ps.categories');

// Búsqueda y recomendación de productos
Route::get('/customers/{customer}/ps/products', [ProductSearchController::class, 'search'])
    ->name('manager.helpdesk.customers.ps.products.search');

// Detalle completo de un producto PS (incluye atributos/combinaciones)
Route::get('/customers/{customer}/ps/products/{productId}', [ProductSearchController::class, 'detail'])
    ->whereNumber('productId')
    ->name('manager.helpdesk.customers.ps.products.detail');

// Alternativas del mismo fabricante/categoría para un producto PS
Route::get('/customers/{customer}/ps/products/{productId}/alternatives', [ProductSearchController::class, 'alternatives'])
    ->whereNumber('productId')
    ->name('manager.helpdesk.customers.ps.products.alternatives');

// Historial de recomendaciones de productos por conversación
Route::prefix('conversations/{conversation}/ps/recommendations')
    ->name('manager.helpdesk.conversations.ps.recommendations.')
    ->group(function () {
        Route::get('/', [PsRecommendationController::class, 'index'])->name('index');
        Route::post('/', [PsRecommendationController::class, 'store'])->name('store');
    });

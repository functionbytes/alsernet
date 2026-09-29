<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\AssistedCartController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\ProductSearchController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsAddressActionsController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsCartActionsController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsCustomerDataController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsOrderActionsController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsOrderDetailController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsRecommendationController;
use Modules\HelpdeskPrestashop\Http\Controllers\Managers\PsVoucherActionsController;

// Order detail — usado por el inbox para enriquecer el modal de pedido PS
Route::get('/ps/orders/{order}/detail', PsOrderDetailController::class)
    ->whereNumber('order')
    ->name('manager.helpdesk.ps.orders.detail');

// Catálogo de estados de pedido PS (desplegable "Cambiar estado" del workspace)
Route::get('/ps/order-states', [PsOrderActionsController::class, 'states'])
    ->name('manager.helpdesk.ps.order-states');

// Acciones mutadoras del pedido PS (workspace del inbox) — throttle por ser
// escritura. Atadas a {customer}: la propiedad del pedido se verifica contra
// el email del cliente resuelto server-side, no de un campo del body.
Route::post('/customers/{customer}/ps/orders/{order}/status', [PsOrderActionsController::class, 'changeStatus'])
    ->whereNumber('order')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.orders.status');

Route::post('/customers/{customer}/ps/orders/{order}/tracking', [PsOrderActionsController::class, 'setTracking'])
    ->whereNumber('order')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.orders.tracking');

Route::post('/customers/{customer}/ps/orders/{order}/note', [PsOrderActionsController::class, 'addNote'])
    ->whereNumber('order')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.orders.note');

Route::post('/customers/{customer}/ps/orders/{order}/return', [PsOrderActionsController::class, 'startReturn'])
    ->whereNumber('order')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.orders.return');

Route::get('/customers/{customer}/ps/orders/{order}/documents', [PsOrderActionsController::class, 'documents'])
    ->whereNumber('order')
    ->name('manager.helpdesk.ps.orders.documents');

Route::post('/customers/{customer}/ps/orders/{order}/address', [PsOrderActionsController::class, 'setAddress'])
    ->whereNumber('order')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.orders.address');

Route::post('/customers/{customer}/ps/orders/{order}/email', [PsOrderActionsController::class, 'sendEmail'])
    ->whereNumber('order')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.orders.email');

// Acciones mutadoras del carrito REAL del cliente en PrestaShop — mismo
// patrón de propiedad/throttle que las de pedido. Requieren el permiso
// dedicado helpdeskprestashop.carts.manage.
Route::post('/customers/{customer}/ps/cart/{cart}/address', [PsCartActionsController::class, 'setAddress'])
    ->whereNumber('cart')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.cart.address');

Route::post('/customers/{customer}/ps/cart/{cart}/products', [PsCartActionsController::class, 'addProduct'])
    ->whereNumber('cart')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.cart.products.add');

Route::delete('/customers/{customer}/ps/cart/{cart}/products', [PsCartActionsController::class, 'removeProduct'])
    ->whereNumber('cart')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.cart.products.remove');

Route::patch('/customers/{customer}/ps/cart/{cart}/products/quantity', [PsCartActionsController::class, 'updateQuantity'])
    ->whereNumber('cart')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.ps.cart.products.quantity');

Route::post('/customers/{customer}/ps/cart/{cart}/voucher', [PsCartActionsController::class, 'applyVoucher'])
    ->whereNumber('cart')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.cart.voucher');

Route::delete('/customers/{customer}/ps/cart/{cart}/voucher', [PsCartActionsController::class, 'removeVoucher'])
    ->whereNumber('cart')
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.ps.cart.voucher.remove');

// Carrito asistido (construido por el agente desde el chat) — DESHABILITADO en
// este proyecto: AssistedCartController depende de Modules\Ecommerce\Services\
// OrderService, y el módulo Ecommerce no se trae a este proyecto.
// Route::prefix('customers/{customer}/cart')
//     ->name('manager.helpdesk.customers.cart.')
//     ->group(function () {
//         Route::get('/', [AssistedCartController::class, 'show'])->name('show');
//         Route::post('/items', [AssistedCartController::class, 'addItem'])->name('items.store');
//         Route::patch('/items/{item}', [AssistedCartController::class, 'updateItem'])->whereNumber('item')->name('items.update');
//         Route::delete('/items/{item}', [AssistedCartController::class, 'removeItem'])->whereNumber('item')->name('items.destroy');
//         Route::post('/discount', [AssistedCartController::class, 'applyDiscount'])->name('discount');
//         Route::post('/clear', [AssistedCartController::class, 'clear'])->name('clear');
//         Route::post('/cancel', [AssistedCartController::class, 'cancel'])->name('cancel');
//         Route::post('/generate-order', [AssistedCartController::class, 'generateOrder'])->name('generate-order');
//         Route::post('/send-payment-link', [AssistedCartController::class, 'sendPaymentLink'])->name('send-payment-link');
//     });
//
// Route::get('/customers/{customer}/carts', [AssistedCartController::class, 'index'])
//     ->name('manager.helpdesk.customers.carts.index');
// Route::get('/customers/{customer}/carts/{cart}', [AssistedCartController::class, 'showCart'])
//     ->whereNumber('cart')
//     ->name('manager.helpdesk.customers.carts.show');

// Direcciones PS del cliente
Route::get('/customers/{customer}/ps/addresses', [PsCustomerDataController::class, 'addresses'])
    ->name('manager.helpdesk.customers.ps.addresses');

// Crear/editar direcciones — requiere helpdeskprestashop.carts.manage (mismo
// nivel de sensibilidad que tocar un carrito antes de pagar).
Route::post('/customers/{customer}/ps/addresses', [PsAddressActionsController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.customers.ps.addresses.store');

Route::patch('/customers/{customer}/ps/addresses/{address}', [PsAddressActionsController::class, 'update'])
    ->whereNumber('address')
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.customers.ps.addresses.update');

// Provincias/estados de un país (desplegable del formulario de dirección)
Route::get('/ps/country-states', [PsCustomerDataController::class, 'countryStates'])
    ->name('manager.helpdesk.ps.country-states');

// Pedidos PS del cliente — carga diferida desde los tabs "Tienda"/"Carritos" del inbox
Route::get('/customers/{customer}/ps/orders', [PsCustomerDataController::class, 'orders'])
    ->name('manager.helpdesk.customers.ps.orders');

// Vale de compensación desde el chat (límite por permiso, ver config vouchers)
Route::post('/customers/{customer}/ps/vouchers', [PsVoucherActionsController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.customers.ps.vouchers.store');

// Devoluciones PS del cliente
Route::get('/customers/{customer}/ps/returns', [PsCustomerDataController::class, 'returns'])
    ->name('manager.helpdesk.customers.ps.returns');

// Vales/cupones propios del cliente
Route::get('/customers/{customer}/ps/vouchers', [PsCustomerDataController::class, 'vouchers'])
    ->name('manager.helpdesk.customers.ps.vouchers');

// Hilos de mensajes nativos de PrestaShop (contacto/atención al cliente)
Route::get('/customers/{customer}/ps/messages', [PsCustomerDataController::class, 'messages'])
    ->name('manager.helpdesk.customers.ps.messages');

// Lista de deseos del cliente
Route::get('/customers/{customer}/ps/wishlist', [PsCustomerDataController::class, 'wishlist'])
    ->name('manager.helpdesk.customers.ps.wishlist');

// Reembolsos reales del cliente (order_slip) — distinto de /returns (RMA)
Route::get('/customers/{customer}/ps/refunds', [PsCustomerDataController::class, 'refunds'])
    ->name('manager.helpdesk.customers.ps.refunds');

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

<?php

use Illuminate\Support\Facades\Route;
use Modules\Erp\Http\Controllers\Api\CustomerController;
use Modules\Erp\Http\Controllers\Api\ErpCredentialsApiController;
use Modules\Erp\Http\Controllers\Api\ErpEndpointsApiController;
use Modules\Erp\Http\Controllers\Api\JerarquiaController;
use Modules\Erp\Http\Controllers\Api\ProductsController;
use Modules\Erp\Http\Controllers\Api\PublicEndpointController;
use Modules\Erp\Http\Controllers\Api\SuppliersController;

Route::middleware(['api'])->group(function () {

    // Authenticated ERP API surface (customer, products, suppliers, families, ...).
    // The `erp.api-auth` middleware is a no-op when config('erp.api.enabled') is false,
    // so the routes stay open behind the firewall by default. Flip ERP_API_AUTH_ENABLED=true
    // and pick the guard via ERP_API_AUTH_GUARD (sanctum|erp_token|both) when ready.
    Route::middleware(['erp.api-auth', 'throttle:'.config('erp.api.throttle', '60,1')])
        ->prefix('erp')
        ->group(function () {

            Route::prefix('customer')->group(function () {
                // Collection
                Route::get('/', [CustomerController::class, 'list']);
                Route::post('/', [CustomerController::class, 'create']);
                Route::patch('/lopd', [CustomerController::class, 'updateLopd']);
                Route::get('/search', [CustomerController::class, 'search']);

                // Lookup por ID web (CODIGO_INTERNET en Oracle)
                Route::get('/search/web/{idweb}', [CustomerController::class, 'findByIdWeb'])->whereNumber('idweb');

                // Customer summary + identity
                Route::get('/{id}', [CustomerController::class, 'summary'])->whereNumber('id');
                Route::get('/{id}/personal', [CustomerController::class, 'personal'])->whereNumber('id');
                Route::get('/{id}/lopd', [CustomerController::class, 'lopd'])->whereNumber('id');

                // Contact / addresses
                Route::get('/{id}/addresses', [CustomerController::class, 'addresses'])->whereNumber('id');
                Route::get('/{id}/contact', [CustomerController::class, 'contact'])->whereNumber('id');

                // Cards / accounts / catalogs / quotas
                Route::get('/{id}/cards', [CustomerController::class, 'cards'])->whereNumber('id');
                Route::get('/{id}/accounts', [CustomerController::class, 'accounts'])->whereNumber('id');
                Route::get('/{id}/catalogs', [CustomerController::class, 'catalogs'])->whereNumber('id');
                Route::get('/{id}/quotas', [CustomerController::class, 'quotas'])->whereNumber('id');

                // Commercial
                Route::get('/{id}/orders', [CustomerController::class, 'orders'])->whereNumber('id');
                Route::get('/{id}/orders/{orderId}', [CustomerController::class, 'orderDetail'])
                    ->whereNumber('id')->whereNumber('orderId');
                Route::get('/{id}/delivery-notes', [CustomerController::class, 'deliveryNotes'])->whereNumber('id');
                Route::get('/{id}/delivery-notes/{deliveryId}', [CustomerController::class, 'deliveryNoteDetail'])
                    ->whereNumber('id')->whereNumber('deliveryId');
                Route::get('/{id}/invoices', [CustomerController::class, 'invoices'])->whereNumber('id');
                Route::get('/{id}/invoices/{invoiceId}', [CustomerController::class, 'invoiceDetail'])
                    ->whereNumber('id')->whereNumber('invoiceId');

                // Financial
                Route::get('/{id}/payments', [CustomerController::class, 'payments'])->whereNumber('id');
                Route::get('/{id}/debts', [CustomerController::class, 'debts'])->whereNumber('id');
                Route::get('/{id}/balance', [CustomerController::class, 'balance'])->whereNumber('id');

                // Promotional
                Route::get('/{id}/vouchers', [CustomerController::class, 'vouchers'])->whereNumber('id');
                Route::get('/{id}/bonuses', [CustomerController::class, 'bonuses'])->whereNumber('id');
                Route::get('/{id}/loyalty-points', [CustomerController::class, 'loyaltyPoints'])->whereNumber('id');

                // Cache
                Route::delete('/{id}/cache', [CustomerController::class, 'clearCache'])->whereNumber('id');
            });

            Route::prefix('families')->group(function () {
                Route::get('/', [JerarquiaController::class, 'indexFamilias']);
                Route::get('/{id}', [JerarquiaController::class, 'showFamilia']);
            });
            Route::prefix('subfamilies')->group(function () {
                Route::get('/', [JerarquiaController::class, 'indexSubfamilias']);
                Route::get('/{id}', [JerarquiaController::class, 'showSubfamilia']);
            });
            Route::prefix('groups')->group(function () {
                Route::get('/', [JerarquiaController::class, 'indexGrupos']);
                Route::get('/{id}', [JerarquiaController::class, 'showGrupo']);
            });

            Route::prefix('suppliers')->group(function () {
                Route::get('/', [SuppliersController::class, 'index']);
                Route::get('/{id}', [SuppliersController::class, 'show']);
                Route::get('/{id}/detailed', [SuppliersController::class, 'showDetailed']);
                Route::delete('/{id}/cache', [SuppliersController::class, 'clearCache']);
                Route::get('/{id}/products', [SuppliersController::class, 'showProducts']);
                Route::get('/{id}/categories', [SuppliersController::class, 'showCategories']);
                Route::get('/{id}/supplier', [SuppliersController::class, 'showSupplier']);
            });

            Route::prefix('products')->group(function () {
                Route::get('/', [ProductsController::class, 'index']);
                Route::get('/filter', [ProductsController::class, 'filter']);
                Route::get('/{id}', [ProductsController::class, 'show']);
                Route::get('/{id}/detailed', [ProductsController::class, 'showDetailed']);
                Route::delete('/{id}/cache', [ProductsController::class, 'clearCache']);
                Route::get('/{id}/supplier', [ProductsController::class, 'showSupplier']);
            });

        });

    // Endpoints management API — exposed at both `/api/erp/endpoints/*` (current
    // panel callers) and `/api/erp/v2/endpoints/*` (test suite + future versioned
    // path). Same controllers, same auth. 'erp.endpoints.manage' añadido junto
    // a auth:sanctum — mismo hueco que en el grupo web (ver ErpServiceProvider),
    // esta API no comprobaba ningún permiso más allá de tener sesión Sanctum.
    foreach (['erp/endpoints', 'erp/v2/endpoints'] as $endpointsPrefix) {
        Route::prefix($endpointsPrefix)->middleware(['auth:sanctum', 'can:erp.endpoints.manage'])->group(function () {
            Route::get('/', [ErpEndpointsApiController::class, 'index']);
            Route::post('/', [ErpEndpointsApiController::class, 'store']);
            Route::get('/{endpoint}', [ErpEndpointsApiController::class, 'show']);
            Route::put('/{endpoint}', [ErpEndpointsApiController::class, 'update']);
            Route::delete('/{endpoint}', [ErpEndpointsApiController::class, 'destroy']);

            Route::post('/{endpoint}/toggle', [ErpEndpointsApiController::class, 'toggle']);
            Route::post('/{endpoint}/test', [ErpEndpointsApiController::class, 'test']);
            Route::get('/{endpoint}/logs', [ErpEndpointsApiController::class, 'logs']);
            Route::delete('/{endpoint}/logs', [ErpEndpointsApiController::class, 'clearLogs']);
            Route::get('/{endpoint}/statistics', [ErpEndpointsApiController::class, 'statistics']);

            Route::prefix('{endpoint}/credentials')->group(function () {
                Route::get('/', [ErpCredentialsApiController::class, 'index']);
                Route::post('/', [ErpCredentialsApiController::class, 'store']);
                Route::get('/{credential}', [ErpCredentialsApiController::class, 'show']);
                Route::put('/{credential}', [ErpCredentialsApiController::class, 'update']);
                Route::delete('/{credential}', [ErpCredentialsApiController::class, 'destroy']);
                Route::post('/{credential}/toggle', [ErpCredentialsApiController::class, 'toggle']);
                Route::post('/{credential}/rotate', [ErpCredentialsApiController::class, 'rotate']);
            });
        });
    }

    // ========== PUBLIC API - TOKEN-BASED ACCESS ==========
    // Throttled to mitigate token-guessing brute force against the URL-param token.
    Route::middleware([
        'erp.validate-endpoint-token',
        'throttle:'.config('erp.api.public_token_throttle', '60,1'),
    ])->group(function () {
        Route::any('/erp/public/{token}/{slug}', [PublicEndpointController::class, 'call']);
    });

});

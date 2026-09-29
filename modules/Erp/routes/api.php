<?php

use Illuminate\Support\Facades\Route;
use Modules\Erp\Http\Controllers\Api\CaracteristicasController;
use Modules\Erp\Http\Controllers\Api\CustomerCommercialController;
use Modules\Erp\Http\Controllers\Api\CustomerController;
use Modules\Erp\Http\Controllers\Api\CustomerFinancialController;
use Modules\Erp\Http\Controllers\Api\CustomerProfileController;
use Modules\Erp\Http\Controllers\Api\CustomerPromotionalController;
use Modules\Erp\Http\Controllers\Api\ErpCredentialsApiController;
use Modules\Erp\Http\Controllers\Api\ErpEndpointsApiController;
use Modules\Erp\Http\Controllers\Api\JerarquiaController;
use Modules\Erp\Http\Controllers\Api\ProductsController;
use Modules\Erp\Http\Controllers\Api\PublicEndpointController;
use Modules\Erp\Http\Controllers\Api\SuppliersController;

Route::middleware(['api', 'erp.server-timing'])->group(function () {

    // ERP API surface (customer, products, suppliers, families, ...).
    // 29-sep-2026: `erp.api-auth` es fail-closed. Con settings.erp_api_auth_enabled='no'
    // solo acepta las IPs de config('erp.api.allowed_ips'); con 'yes' exige el guard
    // de settings.erp_api_auth_guard (sanctum|erp_token|both). Ver ApiAuth.

    // Escritura: token de escritura obligatorio siempre (separado de la lectura).
    Route::middleware(['erp.api-auth:write', 'throttle:'.config('erp.api.throttle', '60,1')])
        ->prefix('erp')
        ->group(function () {
            Route::post('/customer', [CustomerController::class, 'create']);
            Route::patch('/customer/lopd', [CustomerController::class, 'updateLopd']);
            Route::delete('/customer/{id}/cache', [CustomerController::class, 'clearCache'])->whereNumber('id');
            Route::delete('/suppliers/{id}/cache', [SuppliersController::class, 'clearCache']);
            Route::delete('/products/{id}/cache', [ProductsController::class, 'clearCache']);
        });

    Route::middleware(['erp.api-auth', 'throttle:'.config('erp.api.throttle', '60,1')])
        ->prefix('erp')
        ->group(function () {

            Route::prefix('customer')->group(function () {
                // Collection
                Route::get('/', [CustomerController::class, 'list']);
                Route::get('/search', [CustomerController::class, 'search']);

                // Desglose de la audiencia de cumpleaños (?day=MM-DD): cuántos
                // cumplen y cuántos quedan fuera por cada motivo.
                Route::get('/birthday-stats', [CustomerController::class, 'birthdayStats']);

                // Lookup por ID web (CODIGO_INTERNET en Oracle)
                Route::get('/search/web/{idweb}', [CustomerController::class, 'findByIdWeb'])->whereNumber('idweb');

                // Customer summary + identity
                Route::get('/{id}', [CustomerController::class, 'summary'])->whereNumber('id');
                Route::get('/{id}/personal', [CustomerProfileController::class, 'personal'])->whereNumber('id');
                Route::get('/{id}/lopd', [CustomerProfileController::class, 'lopd'])->whereNumber('id');

                // Contact / addresses
                Route::get('/{id}/addresses', [CustomerProfileController::class, 'addresses'])->whereNumber('id');
                Route::get('/{id}/contact', [CustomerProfileController::class, 'contact'])->whereNumber('id');

                // Cards / accounts / catalogs / quotas
                Route::get('/{id}/cards', [CustomerProfileController::class, 'cards'])->whereNumber('id');
                Route::get('/{id}/accounts', [CustomerProfileController::class, 'accounts'])->whereNumber('id');
                Route::get('/{id}/catalogs', [CustomerProfileController::class, 'catalogs'])->whereNumber('id');
                Route::get('/{id}/quotas', [CustomerProfileController::class, 'quotas'])->whereNumber('id');

                // Commercial
                Route::get('/{id}/orders', [CustomerCommercialController::class, 'orders'])->whereNumber('id');
                Route::get('/{id}/orders/{orderId}', [CustomerCommercialController::class, 'orderDetail'])
                    ->whereNumber('id')->whereNumber('orderId');
                Route::get('/{id}/delivery-notes', [CustomerCommercialController::class, 'deliveryNotes'])->whereNumber('id');
                Route::get('/{id}/delivery-notes/{deliveryId}', [CustomerCommercialController::class, 'deliveryNoteDetail'])
                    ->whereNumber('id')->whereNumber('deliveryId');
                Route::get('/{id}/invoices', [CustomerCommercialController::class, 'invoices'])->whereNumber('id');
                Route::get('/{id}/invoices/{invoiceId}', [CustomerCommercialController::class, 'invoiceDetail'])
                    ->whereNumber('id')->whereNumber('invoiceId');

                // Financial
                Route::get('/{id}/payments', [CustomerFinancialController::class, 'payments'])->whereNumber('id');
                Route::get('/{id}/debts', [CustomerFinancialController::class, 'debts'])->whereNumber('id');
                Route::get('/{id}/balance', [CustomerFinancialController::class, 'balance'])->whereNumber('id');

                // Promotional
                Route::get('/{id}/vouchers', [CustomerPromotionalController::class, 'vouchers'])->whereNumber('id');
                Route::get('/{id}/bonuses', [CustomerPromotionalController::class, 'bonuses'])->whereNumber('id');
                Route::get('/{id}/loyalty-points', [CustomerPromotionalController::class, 'loyaltyPoints'])->whereNumber('id');
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

            Route::prefix('characteristics')->group(function () {
                Route::get('/', [CaracteristicasController::class, 'indexCaracteristicas']);
            });
            Route::prefix('characteristic-values')->group(function () {
                Route::get('/', [CaracteristicasController::class, 'indexValores']);
            });
            Route::prefix('model-characteristics')->group(function () {
                Route::get('/', [CaracteristicasController::class, 'indexModeloCaracteristicas']);
            });
            Route::prefix('variant-characteristics')->group(function () {
                Route::get('/', [CaracteristicasController::class, 'indexVarianteCaracteristicas']);
            });

            Route::prefix('suppliers')->group(function () {
                Route::get('/', [SuppliersController::class, 'index']);
                Route::get('/{id}', [SuppliersController::class, 'show']);
                Route::get('/{id}/detailed', [SuppliersController::class, 'showDetailed']);
                Route::get('/{id}/products', [SuppliersController::class, 'showProducts']);
                Route::get('/{id}/categories', [SuppliersController::class, 'showCategories']);
                Route::get('/{id}/supplier', [SuppliersController::class, 'showSupplier']);
            });

            Route::prefix('products')->group(function () {
                Route::get('/', [ProductsController::class, 'index']);
                Route::get('/filter', [ProductsController::class, 'filter']);
                Route::get('/{id}', [ProductsController::class, 'show']);
                Route::get('/{id}/detailed', [ProductsController::class, 'showDetailed']);
                Route::get('/{id}/supplier', [ProductsController::class, 'showSupplier']);
            });

        });

    // Endpoints management API — exposed at both `/api/erp/endpoints/*` (current
    // panel callers) and `/api/erp/v2/endpoints/*` (test suite + future versioned
    // path). Same controllers, same auth. 'erp.endpoints.manage' añadido junto
    // a auth:sanctum — mismo hueco que en el grupo web (ver ErpServiceProvider),
    // esta API no comprobaba ningún permiso más allá de tener sesión Sanctum.
    foreach (['erp/endpoints', 'erp/v2/endpoints'] as $endpointsPrefix) {
        // El prefijo sin versión queda obsoleto: responde igual pero con
        // cabecera Deprecation apuntando a v2. Migrar el panel y retirarlo.
        $endpointsMiddleware = ['auth:sanctum', 'can:erp.endpoints.manage'];
        if ($endpointsPrefix === 'erp/endpoints') {
            $endpointsMiddleware[] = 'erp.deprecated:/api/erp/v2/endpoints';
        }

        Route::prefix($endpointsPrefix)->middleware($endpointsMiddleware)->group(function () {
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

<?php

use Illuminate\Support\Facades\Route;
use Modules\Erp\Http\Controllers\ErpSettingsController;
use Modules\Erp\Http\Controllers\OracleDatabaseController;
use Modules\Erp\Http\Controllers\Settings\ErpCredentialsController;
use Modules\Erp\Http\Controllers\Settings\ErpDashboardController;
use Modules\Erp\Http\Controllers\Settings\ErpEndpointsController;

/*
|--------------------------------------------------------------------------
| ERP Module Routes
|--------------------------------------------------------------------------
|
| All routes for ERP management interface
| Middleware and prefix are applied by ErpServiceProvider
|
*/

// Dashboard
Route::get('/', [ErpSettingsController::class, 'index'])->name('index');

// Metrics Dashboard
Route::prefix('/dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [ErpDashboardController::class, 'index'])->name('index');
    Route::get('/metrics', [ErpDashboardController::class, 'getMetricsData'])->name('metrics');
});

// ========== API Settings Routes ==========
Route::prefix('/api')->name('api.')->group(function () {
    Route::get('/', [ErpSettingsController::class, 'edit'])->name('edit');
    Route::put('/update', [ErpSettingsController::class, 'update'])->name('update');
    Route::post('/check-services', [ErpSettingsController::class, 'checkServices'])->name('check-services');
    Route::post('/test-sync', [ErpSettingsController::class, 'testSync'])->name('test-sync');
    Route::post('/clear-cache', [ErpSettingsController::class, 'clearCache'])->name('clear-cache');
    Route::post('/reset-stats', [ErpSettingsController::class, 'resetStats'])->name('reset-stats');
    Route::get('/get-stats', [ErpSettingsController::class, 'getStats'])->name('get-stats');
    Route::post('/toggle-active', [ErpSettingsController::class, 'toggleActive'])->name('toggle-active');
});

// ========== API Security (auth) Routes ==========
Route::prefix('/api-security')->name('api-security.')->group(function () {
    Route::get('/', [ErpSettingsController::class, 'editApiSecurity'])->name('edit');
    Route::put('/', [ErpSettingsController::class, 'updateApiSecurity'])->name('update');
});

// ========== Database Settings Routes ==========
Route::prefix('/database')->name('database.')->group(function () {
    Route::get('/', [OracleDatabaseController::class, 'index'])->name('index');
    Route::get('/edit', [OracleDatabaseController::class, 'edit'])->name('edit');
    Route::put('/update', [OracleDatabaseController::class, 'update'])->name('update');
    Route::post('/check-connection', [OracleDatabaseController::class, 'checkConnection'])->name('check-connection');
});

// ========== Endpoints Management Routes ==========
// Gestión de endpoints ERP salientes: exige permiso dedicado. Antes solo
// pedía ['web','auth','verified'] → cualquier usuario autenticado podía crear
// endpoints, ejecutarlos (SSRF) y generar/ver tokens públicos.
Route::prefix('/endpoints')->name('endpoints.')->middleware('can:erp.endpoints.manage')->group(function () {
    // Endpoints CRUD
    Route::get('/', [ErpEndpointsController::class, 'index'])->name('index');
    Route::get('/create', [ErpEndpointsController::class, 'create'])->name('create');
    Route::post('/', [ErpEndpointsController::class, 'store'])->name('store');
    Route::get('/{endpoint}', [ErpEndpointsController::class, 'show'])->name('show');
    Route::get('/{endpoint}/edit', [ErpEndpointsController::class, 'edit'])->name('edit');
    Route::put('/{endpoint}', [ErpEndpointsController::class, 'update'])->name('update');
    Route::delete('/{endpoint}', [ErpEndpointsController::class, 'destroy'])->name('destroy');

    // Endpoint actions
    Route::post('/{endpoint}/toggle', [ErpEndpointsController::class, 'toggle'])->name('toggle');
    Route::post('/{endpoint}/test', [ErpEndpointsController::class, 'test'])->name('test');
    Route::delete('/{endpoint}/logs', [ErpEndpointsController::class, 'clearLogs'])->name('logs.clear');

    // Token management
    Route::post('/{endpoint}/tokens', [ErpEndpointsController::class, 'generateToken'])->name('tokens.generate');
    Route::delete('/tokens/{token}', [ErpEndpointsController::class, 'revokeToken'])->name('tokens.revoke');
    Route::get('/tokens/{token}/value', [ErpEndpointsController::class, 'getTokenValue'])->name('tokens.value');

    // Nested Credentials Management under Endpoints
    Route::prefix('/{endpoint}/credentials')->name('credentials.')->group(function () {
        Route::get('/', [ErpCredentialsController::class, 'index'])->name('index');
        Route::get('/create', [ErpCredentialsController::class, 'create'])->name('create');
        Route::post('/', [ErpCredentialsController::class, 'store'])->name('store');
        Route::get('/{credential}', [ErpCredentialsController::class, 'show'])->name('show');
        Route::get('/{credential}/edit', [ErpCredentialsController::class, 'edit'])->name('edit');
        Route::put('/{credential}', [ErpCredentialsController::class, 'update'])->name('update');
        Route::delete('/{credential}', [ErpCredentialsController::class, 'destroy'])->name('destroy');
        Route::post('/{credential}/toggle', [ErpCredentialsController::class, 'toggle'])->name('toggle');
        Route::post('/{credential}/test', [ErpCredentialsController::class, 'test'])->name('test');
        Route::post('/{credential}/rotate', [ErpCredentialsController::class, 'rotate'])->name('rotate');
    });
});

// ========== Top-level Credentials Management Routes (for standalone access) ==========
// Mismo gate que el grupo hermano /endpoints: sin este middleware, cualquier
// usuario autenticado+verificado podía crear/rotar/borrar credenciales ERP
// (el controller no tiene authorize() interno y el ServiceProvider solo aplica
// ['web','auth','verified']).
Route::prefix('/credentials')->name('credentials.')->middleware('can:erp.endpoints.manage')->group(function () {
    // Credentials CRUD
    Route::get('/', [ErpCredentialsController::class, 'index'])->name('index');
    Route::get('/create', [ErpCredentialsController::class, 'create'])->name('create');
    Route::post('/', [ErpCredentialsController::class, 'store'])->name('store');
    Route::get('/{credential}', [ErpCredentialsController::class, 'show'])->name('show');
    Route::get('/{credential}/edit', [ErpCredentialsController::class, 'edit'])->name('edit');
    Route::put('/{credential}', [ErpCredentialsController::class, 'update'])->name('update');
    Route::delete('/{credential}', [ErpCredentialsController::class, 'destroy'])->name('destroy');

    // Credential actions
    Route::post('/{credential}/toggle', [ErpCredentialsController::class, 'toggle'])->name('toggle');
    Route::post('/{credential}/test', [ErpCredentialsController::class, 'test'])->name('test');
    Route::post('/{credential}/rotate', [ErpCredentialsController::class, 'rotate'])->name('rotate');
});

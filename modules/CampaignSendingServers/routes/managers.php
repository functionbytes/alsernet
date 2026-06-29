<?php

use Illuminate\Support\Facades\Route;
use Modules\CampaignSendingServers\Http\Controllers\Managers\BlacklistController;
use Modules\CampaignSendingServers\Http\Controllers\Managers\SendingDomainController;
use Modules\CampaignSendingServers\Http\Controllers\Managers\SendingServerController;
use Modules\CampaignSendingServers\Http\Controllers\Managers\TrackingDomainController;

/*
|--------------------------------------------------------------------------
| CampaignSendingServers - Manager Routes
|--------------------------------------------------------------------------
| Cargadas por CampaignSendingServersServiceProvider con:
|   - prefix: panel/sending-servers
|   - name:   manager.sending-servers.
|   - middleware: web, auth, role:super-admin|super-settings
*/

// Servidores de envío — rutas estáticas primero
Route::get('/', [SendingServerController::class, 'index'])->name('index');
Route::get('/create', [SendingServerController::class, 'create'])->name('create');
Route::post('/', [SendingServerController::class, 'store'])->name('store');

// Dominios de envío (DKIM/SPF)
Route::group(['prefix' => 'domains', 'as' => 'domains.'], function () {
    Route::get('/', [SendingDomainController::class, 'index'])->name('index');
    Route::get('/create', [SendingDomainController::class, 'create'])->name('create');
    Route::post('/', [SendingDomainController::class, 'store'])->name('store');
    Route::get('/{uid}', [SendingDomainController::class, 'show'])->name('show');
    Route::delete('/{uid}', [SendingDomainController::class, 'destroy'])->name('destroy');
    Route::post('/{uid}/verify', [SendingDomainController::class, 'verify'])->name('verify');
});

// Dominios de tracking
Route::group(['prefix' => 'tracking-domains', 'as' => 'tracking-domains.'], function () {
    Route::get('/', [TrackingDomainController::class, 'index'])->name('index');
    Route::get('/create', [TrackingDomainController::class, 'create'])->name('create');
    Route::post('/', [TrackingDomainController::class, 'store'])->name('store');
    Route::get('/{uid}', [TrackingDomainController::class, 'show'])->name('show');
    Route::delete('/{uid}', [TrackingDomainController::class, 'destroy'])->name('destroy');
    Route::post('/{uid}/verify', [TrackingDomainController::class, 'verify'])->name('verify');
});

// Lista negra
Route::group(['prefix' => 'blacklist', 'as' => 'blacklist.'], function () {
    Route::get('/', [BlacklistController::class, 'index'])->name('index');
    Route::post('/', [BlacklistController::class, 'store'])->name('store');
    Route::delete('/{id}', [BlacklistController::class, 'destroy'])->name('destroy');
    Route::post('/import', [BlacklistController::class, 'import'])->name('import');
});

// Servidores de envío — rutas con wildcard {uid} al final para no capturar prefijos estáticos
Route::get('/{uid}', [SendingServerController::class, 'show'])->name('show');
Route::get('/{uid}/edit', [SendingServerController::class, 'edit'])->name('edit');
Route::put('/{uid}', [SendingServerController::class, 'update'])->name('update');
Route::delete('/{uid}', [SendingServerController::class, 'destroy'])->name('destroy');
Route::post('/{uid}/test', [SendingServerController::class, 'test'])->name('test');
Route::post('/{uid}/verify', [SendingServerController::class, 'verify'])->name('verify');

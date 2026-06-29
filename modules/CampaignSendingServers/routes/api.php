<?php

use Illuminate\Support\Facades\Route;
use Modules\CampaignSendingServers\Http\Controllers\Api\SendingServerController;

/*
|--------------------------------------------------------------------------
| CampaignSendingServers - API Routes
|--------------------------------------------------------------------------
| Cargadas por CampaignSendingServersServiceProvider con:
|   - prefix: api/sending-servers
|   - name:   api.sending-servers.
|   - middleware: api, auth:sanctum
*/

Route::get('/', [SendingServerController::class, 'index'])->name('index');
Route::post('/', [SendingServerController::class, 'store'])->name('store');
Route::get('/{uid}', [SendingServerController::class, 'show'])->name('show');
Route::put('/{uid}', [SendingServerController::class, 'update'])->name('update');
Route::delete('/{uid}', [SendingServerController::class, 'destroy'])->name('destroy');
// Test es operación cara (abre conexión SMTP/HTTP) → throttle más agresivo.
Route::post('/{uid}/test', [SendingServerController::class, 'test'])
    ->name('test')
    ->middleware('throttle:5,1');

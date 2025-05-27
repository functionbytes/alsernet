<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ErpController;
use App\Http\Controllers\Api\Return\PublicReturnController;
use App\Http\Controllers\Api\Return\ReturnController;
use App\Http\Controllers\Api\SubscribersController;
use App\Http\Controllers\Api\TicketsController;
use App\Http\Controllers\Api\DocumentsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'subscribers'], function () {
    Route::post('/', [SubscribersController::class, 'process']);
    Route::put('/replace', [SubscribersController::class, 'replace']);
    Route::patch('patch', [SubscribersController::class, 'update']);
    Route::post('process', [SubscribersController::class, 'process']);
    Route::post('campaigns', [SubscribersController::class, 'campaigns']);
});


Route::group(['prefix' => 'subscribers'], function () {
    Route::post('/', [SubscribersController::class, 'process']);
    Route::put('/replace', [SubscribersController::class, 'replace']);
    Route::patch('patch', [SubscribersController::class, 'update']);
    Route::post('process', [SubscribersController::class, 'process']);
    Route::post('campaigns', [SubscribersController::class, 'campaigns']);
    Route::get('synchronization', [SubscribersController::class, 'synchronization']);
});

Route::group(['prefix' => 'documents'], function () {
    Route::post('/', [DocumentsController::class, 'process']);
});

Route::middleware('auth:sanctum')->group(function() {

    Route::apiResource('tickets', TicketsController::class)->except(['update']);
    Route::put('tickets/{ticket}', [TicketsController::class, 'replace']);
    Route::patch('tickets/{ticket}', [TicketsController::class, 'update']);

});

Route::group(['prefix' => 'erp'], function () {
    Route::post('recuperarclienteerp', [ErpController::class, 'recuperarclienteerp']);
    Route::post('recuperaridclienteerp', [ErpController::class, 'recuperaridclienteerp']);
    Route::post('recuperarpedidoscliente', [ErpController::class, 'recuperarpedidoscliente']);
    Route::post('recuperarpedido', [ErpController::class, 'recuperarpedido']);
    Route::post('recuperarpedidoporid', [ErpController::class, 'recuperarpedidoporid']);
    Route::post('recuperarclienteerpAlsernet', [ErpController::class, 'recuperarclienteerpAlsernet']);
    Route::post('recuperardatosclienteerp', [ErpController::class, 'recuperardatosclienteerp']);
    Route::post('recuperardatosclienteerpporidweb', [ErpController::class, 'recuperardatosclienteerpporidweb']);
    Route::post('recuperardatosclienteerpporidgestion', [ErpController::class, 'recuperardatosclienteerpporidgestion']);
    Route::post('getIdiomaGestion', [ErpController::class, 'getIdiomaGestion']);
    Route::post('getPaisGestion', [ErpController::class, 'getPaisGestion']);
    Route::post('guardardatosclienteerp', [ErpController::class, 'guardardatosclienteerp']);
    Route::post('recuperarcatalogosclienteerp', [ErpController::class, 'recuperarcatalogosclienteerp']);
    Route::post('suscribircatalogosporeamilerp', [ErpController::class, 'suscribircatalogosporeamilerp']);
    Route::post('delsuscribircatalogosporeamilerp', [ErpController::class, 'delsuscribircatalogosporeamilerp']);
    Route::post('savelopd', [ErpController::class, 'savelopd']);
    Route::post('recuperarstockcentral', [ErpController::class, 'recuperarstockcentral']);
    Route::post('recuperaridarticulo', [ErpController::class, 'recuperaridarticulo']);
    Route::post('consultabono', [ErpController::class, 'consultabono']);
    Route::post('marcarbono', [ErpController::class, 'marcarbono']);
    Route::post('consultavalecompra', [ErpController::class, 'consultavalecompra']);
    Route::post('actualizarvalecompra', [ErpController::class, 'actualizarvalecompra']);
    Route::post('crearvalecompra', [ErpController::class, 'crearvalecompra']);
    Route::post('tienetarifaplana', [ErpController::class, 'tienetarifaplana']);
    Route::post('toGestion', [ErpController::class, 'toGestion']);
    Route::post('construirdatospedido', [ErpController::class, 'construirdatospedido']);
    Route::post('isMobilePhone', [ErpController::class, 'isMobilePhone']);
    Route::post('mandarpedido', [ErpController::class, 'mandarpedido']);
    Route::post('forma_pago', [ErpController::class, 'forma_pago']);
});



// Rutas públicas - sin autenticación
Route::prefix('returns')->group(function () {
    Route::get('/status', [PublicReturnController::class, 'getStatus']);
    Route::post('/guest', [PublicReturnController::class, 'createGuestReturn']);
    Route::get('/form-data', [ReturnController::class, 'getFormData']);
});

// Rutas autenticadas para clientes
Route::middleware('auth:sanctum')->prefix('returns')->group(function () {
    Route::get('/', [ReturnController::class, 'index']);
    Route::get('/{id}', [ReturnController::class, 'show']);
    Route::post('/', [ReturnController::class, 'store']);
    Route::get('/{id}/pdf', [ReturnController::class, 'downloadPDF']);
});

// Rutas administrativas
Route::prefix('admin/returns')->middleware('auth:admin')->group(function () {
    Route::get('/', [AdminReturnController::class, 'index']);
    Route::get('/statistics', [AdminReturnController::class, 'getStatistics']);
    Route::get('/{id}', [AdminReturnController::class, 'show']);
    Route::post('/', [AdminReturnController::class, 'store']);
    Route::put('/{id}', [AdminReturnController::class, 'update']);
    Route::delete('/{id}', [AdminReturnController::class, 'destroy']);
    Route::post('/{id}/status', [AdminReturnController::class, 'updateStatus']);
    Route::post('/{id}/discussion', [AdminReturnController::class, 'addDiscussion']);
    Route::get('/{id}/pdf', [AdminReturnController::class, 'downloadPDF']);
});

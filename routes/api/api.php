<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ErpController;
use App\Http\Controllers\Api\SubscribersController;
use App\Http\Controllers\Api\TicketsController;
use App\Http\Controllers\Api\ComparatorController;
use App\Http\Controllers\Api\DocumentsController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;


Route::group(['prefix' => 'comparators'], function () {
    Route::get('/get/{comparator}/{iso}', [ComparatorController::class, 'get']);
    Route::get('/process', [ComparatorController::class, 'process']);

    Route::get('/test-mongo', function () {
        DB::connection('mongodb')->collection('test')->insert([
            'mensaje' => 'Funciona Mongo desde Laravel!'
        ]);

        return 'OK';
    });

    Route::get('/test-mongo-insert', function () {
        DB::connection('mongodb')
            ->collection('comparador_es')
            ->insert([
                'product_code' => 'demo-test-' . now()->timestamp,
                'competitors' => [
                    [
                        'marketplace' => 'amazon.es',
                        'seller' => 'Prueba Seller',
                        'price' => 123.45,
                        'quantity' => 10,
                        'url' => 'https://amazon.es/producto',
                        'shipping' => 4.99,
                        'date' => now()->toDateTimeString(),
                    ]
                ]
            ]);

        return 'Insert MongoDB OK';
    });

    Route::get('/mongo-test', function () {
        $entry = [
            'product_code' => Str::random(10),
            'competitors' => [
                [
                    'marketplace' => 'test.com',
                    'seller' => 'Seller A',
                    'price' => 12.34,
                    'quantity' => 5,
                    'url' => 'https://test.com/product',
                    'shipping' => 3.50,
                    'date' => now()->toDateTimeString(),
                ]
            ]
        ];

        //dd($entry);
        DB::connection('mongodb')
            ->collection('comparador_es')
            ->updateOne(
                ['product_code' => $entry['product_code']],
                ['$set' => $entry],
                ['upsert' => true]
            );

        return 'Test insert done.';
    });

});



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

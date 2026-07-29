<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PrestaShop API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the PrestaShop module.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/

// Webhook para pedidos pagados de PrestaShop
Route::post('/webhook/order-paid', function () {
    // TODO: Mover lógica desde routes/api.php principal
    return response()->json(['status' => 'pending']);
})->name('webhook.order-paid');

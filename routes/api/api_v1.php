<?php

use App\Http\Controllers\Api\V1\SubscribersController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\AuthorsController;
use App\Http\Controllers\Api\V1\AuthorTicketsController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\NewslettersController;
use App\Http\Controllers\AuthController;
use App\Http\Resources\V1\SubscriberResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'subscribers'], function () {
    Route::post('/', [SubscribersController::class, 'process']);
    Route::put('/replace', [SubscribersController::class, 'replace']);
    Route::patch('patch', [SubscribersController::class, 'update']);
    Route::post('process', [SubscribersController::class, 'process']);
    Route::post('campaigns', [SubscribersController::class, 'campaigns']);

});

Route::middleware('auth:sanctum')->group(function() {

    Route::apiResource('tickets', TicketController::class)->except(['update']);
    Route::put('tickets/{ticket}', [TicketController::class, 'replace']);
    Route::patch('tickets/{ticket}', [TicketController::class, 'update']);

    Route::apiResource('users', UserController::class)->except(['update']);
    Route::put('users/{user}', [UserController::class, 'replace']);
    Route::patch('users/{user}', [UserController::class, 'update']);

    Route::apiResource('authors', AuthorsController::class)->except(['store','update','delete']);
    Route::apiResource('authors.tickets', AuthorTicketsController::class)->except(['update']);
    Route::put('authors/{author}/tickets/{ticket}', [AuthorTicketsController::class, 'replace']);
    Route::patch('authors/{author}/tickets/{ticket}', [AuthorTicketsController::class, 'update']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});



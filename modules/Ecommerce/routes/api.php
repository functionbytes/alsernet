<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\AddressApiController;
use Modules\Ecommerce\Http\Controllers\Api\CartApiController;
use Modules\Ecommerce\Http\Controllers\Api\CustomerApiController;
use Modules\Ecommerce\Http\Controllers\Api\OrderApiController;
use Modules\Ecommerce\Http\Controllers\Api\ProductApiController;
use Modules\Ecommerce\Http\Controllers\Api\ReviewApiController;
use Modules\Ecommerce\Http\Controllers\Api\WishlistApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Ecommerce Module
|--------------------------------------------------------------------------
*/

Route::prefix('v1/ecommerce')->name('api.ecommerce.')->group(function () {

    // Public product endpoints
    Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('products.show');
    Route::get('/categories', [ProductApiController::class, 'categories'])->name('categories.index');
    Route::get('/brands', [ProductApiController::class, 'brands'])->name('brands.index');

    // Cart (guest or authenticated)
    Route::get('/cart', [CartApiController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartApiController::class, 'store'])->name('cart.store');
    Route::put('/cart/{rowId}', [CartApiController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{rowId}', [CartApiController::class, 'destroy'])->name('cart.destroy');

    // Public reviews
    Route::get('/products/{product}/reviews', [ReviewApiController::class, 'index'])->name('products.reviews.index');

    // Authenticated customer routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderApiController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderApiController::class, 'show'])->name('orders.show');
        Route::post('/orders', [OrderApiController::class, 'store'])->name('orders.store');

        Route::get('/profile', [CustomerApiController::class, 'profile'])->name('profile');
        Route::put('/profile', [CustomerApiController::class, 'updateProfile'])->name('profile.update');

        // Wishlist
        Route::get('/wishlist', [WishlistApiController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{product}', [WishlistApiController::class, 'store'])->name('wishlist.store');
        Route::delete('/wishlist/{wishlist}', [WishlistApiController::class, 'destroy'])->name('wishlist.destroy');

        // Reviews
        Route::post('/products/{product}/reviews', [ReviewApiController::class, 'store'])->name('products.reviews.store');

        // Addresses
        Route::get('/addresses', [AddressApiController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [AddressApiController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address}', [AddressApiController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressApiController::class, 'destroy'])->name('addresses.destroy');
    });
});

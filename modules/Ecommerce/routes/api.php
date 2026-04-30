<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\AddressApiController;
use Modules\Ecommerce\Http\Controllers\Api\CartApiController;
use Modules\Ecommerce\Http\Controllers\Api\CompareApiController;
use Modules\Ecommerce\Http\Controllers\Api\CountryApiController;
use Modules\Ecommerce\Http\Controllers\Api\CouponApiController;
use Modules\Ecommerce\Http\Controllers\Api\CustomerApiController;
use Modules\Ecommerce\Http\Controllers\Api\DigitalDownloadController;
use Modules\Ecommerce\Http\Controllers\Api\EmailTrackingController;
use Modules\Ecommerce\Http\Controllers\Api\OrderApiController;
use Modules\Ecommerce\Http\Controllers\Api\ProductApiController;
use Modules\Ecommerce\Http\Controllers\Api\ProductFilterApiController;
use Modules\Ecommerce\Http\Controllers\Api\ReviewApiController;
use Modules\Ecommerce\Http\Controllers\Api\TaxApiController;
use Modules\Ecommerce\Http\Controllers\Api\WishlistApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Ecommerce Module
|--------------------------------------------------------------------------
*/

Route::prefix('v1/ecommerce')->name('api.ecommerce.')->group(function () {

    // Email tracking (no auth — embedded in emails)
    Route::get('/email-tracking/open/{token}', [EmailTrackingController::class, 'open'])->name('email-tracking.open');
    Route::get('/email-tracking/click/{token}', [EmailTrackingController::class, 'click'])->name('email-tracking.click');

    // Public product endpoints
    Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
    Route::get('/products/suggestions', [ProductApiController::class, 'suggestions'])->name('products.suggestions');
    Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('products.show');
    Route::get('/categories', [ProductApiController::class, 'categories'])->name('categories.index');
    Route::get('/brands', [ProductApiController::class, 'brands'])->name('brands.index');

    // Cart (guest or authenticated)
    Route::get('/cart/count', [CartApiController::class, 'count'])->name('cart.count');
    Route::get('/cart', [CartApiController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartApiController::class, 'store'])->name('cart.store');
    Route::put('/cart/{rowId}', [CartApiController::class, 'update'])->name('cart.update')->whereNumber('rowId');
    Route::delete('/cart/{rowId}', [CartApiController::class, 'destroy'])->name('cart.destroy')->whereNumber('rowId');

    // Public reviews
    Route::get('/products/{product}/reviews', [ReviewApiController::class, 'index'])->name('products.reviews.index');

    // Coupons (public with throttle)
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/coupons/apply', [CouponApiController::class, 'apply'])->name('coupons.apply');
        Route::delete('/coupons/remove', [CouponApiController::class, 'remove'])->name('coupons.remove');
    });

    // Compare (public with throttle)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/compare', [CompareApiController::class, 'index'])->name('compare.index');
        Route::post('/compare', [CompareApiController::class, 'add'])->name('compare.add');
        Route::delete('/compare', [CompareApiController::class, 'remove'])->name('compare.remove');
    });

    // Product filters (public with throttle)
    Route::get('/filters', [ProductFilterApiController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('filters');

    // Tax calculation (public with throttle)
    Route::post('/taxes/calculate', [TaxApiController::class, 'calculate'])
        ->middleware('throttle:60,1')
        ->name('taxes.calculate');

    // Countries (public, no auth)
    Route::get('/countries', [CountryApiController::class, 'index'])->name('countries.index');

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

        // Digital downloads
        Route::get('/downloads/{item}', [DigitalDownloadController::class, 'download'])->name('downloads');
    });
});

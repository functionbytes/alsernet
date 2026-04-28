<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\AddressController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\AuthController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\AvatarController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\BrandController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\CartController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\CatalogController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\CategoryController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\DeviceController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\FilterController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\LegalPageController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\NewsletterController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\OrderController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\PaymentController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\ProfileController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\RecentlyViewedController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\RestockAlertController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\ReviewController;
use Modules\Ecommerce\Http\Controllers\Api\V1\Customer\WishlistController;

/*
|--------------------------------------------------------------------------
| Ecommerce Mobile API Routes (v1)
|--------------------------------------------------------------------------
|
| Loaded by Modules\Ecommerce\Providers\RouteServiceProvider::mapApiMobileRoutes()
| with prefix 'api/v1' and middleware 'api'.
|
| Audience: customer (Sanctum tokens issued from Modules\Ecommerce\Models\Customer)
*/

Route::prefix('ecommerce/auth')
    ->name('api.v1.ecommerce.auth.')
    ->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth-register')
            ->name('register');

        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login')
            ->name('login');

        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth-forgot')
            ->name('forgot-password');

        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->name('reset-password');

        Route::post('verify-email/{token}', [AuthController::class, 'verifyEmail'])
            ->name('verify-email');

        Route::post('social/{provider}', [AuthController::class, 'social'])
            ->middleware('throttle:auth-login')
            ->name('social');

        Route::middleware(['auth:sanctum', 'customer'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
            Route::post('resend-verification', [AuthController::class, 'resendVerification'])->name('resend-verification');
        });
    });

/*
| Public catalog (read-only)
*/
Route::middleware(['accept-language', 'throttle:api-mobile'])
    ->prefix('ecommerce')
    ->name('api.v1.ecommerce.')
    ->group(function () {
        Route::get('products', [CatalogController::class, 'index'])->name('products.index');
        Route::get('products/suggestions', [CatalogController::class, 'suggestions'])
            ->middleware('throttle:60,1')
            ->name('products.suggestions');
        Route::get('products/{slug}', [CatalogController::class, 'show'])->name('products.show');
        Route::get('products/{slug}/related', [CatalogController::class, 'related'])->name('products.related');
        Route::get('products/{product:slug}/reviews', [ReviewController::class, 'index'])->name('products.reviews.index');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/{slug}', [BrandController::class, 'show'])->name('brands.show');

        Route::get('filters', [FilterController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('filters.index');

        Route::get('legal-pages/{slug}', [LegalPageController::class, 'show'])->name('legal-pages.show');

        Route::post('products/{product}/restock-alerts', [RestockAlertController::class, 'store'])->name('products.restock-alerts.store');
        Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:30,1')->name('newsletter.subscribe');
    });

/*
| Authenticated customer area (Sanctum + audience guard)
*/
Route::middleware(['accept-language', 'auth:sanctum', 'customer', 'throttle:api-mobile'])->group(function () {
    Route::post('me/avatar', [AvatarController::class, 'update'])
        ->middleware('idempotency')
        ->name('api.v1.me.avatar.update');

    Route::delete('me/avatar', [AvatarController::class, 'destroy'])->name('api.v1.me.avatar.destroy');

    Route::delete('me', [ProfileController::class, 'destroy'])->name('api.v1.me.destroy');

    Route::post('me/devices', [DeviceController::class, 'store'])
        ->middleware('idempotency')
        ->name('api.v1.me.devices.store');

    Route::delete('me/devices/{device}', [DeviceController::class, 'destroy'])
        ->name('api.v1.me.devices.destroy');

    Route::prefix('ecommerce')->name('api.v1.ecommerce.')->group(function () {
        Route::apiResource('addresses', AddressController::class);
        Route::post('addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('wishlist/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart/items', [CartController::class, 'store'])->name('cart.items.store');
        Route::put('cart/items/{cart}', [CartController::class, 'update'])->name('cart.items.update');
        Route::delete('cart/items/{cart}', [CartController::class, 'destroy'])->name('cart.items.destroy');
        Route::post('cart/coupons', [CartController::class, 'applyCoupon'])->middleware('throttle:30,1')->name('cart.coupons.apply');
        Route::delete('cart/coupons', [CartController::class, 'removeCoupon'])->name('cart.coupons.remove');

        Route::get('payment-methods', [PaymentController::class, 'methods'])->name('payment-methods.index');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->middleware('idempotency')->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/payments', [PaymentController::class, 'initiate'])->middleware('idempotency')->name('orders.payments.initiate');
        Route::get('orders/{order}/payments/{payment}', [PaymentController::class, 'status'])->name('orders.payments.status');

        // Reviews CRUD (verified buyer for store)
        Route::post('products/{product}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
        Route::put('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    // Recently viewed (relación con auth user)
    Route::get('me/recently-viewed', [RecentlyViewedController::class, 'index'])->name('api.v1.me.recently-viewed');
});

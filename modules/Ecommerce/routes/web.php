<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Admin\BrandController;
use Modules\Ecommerce\Http\Controllers\Admin\CustomerAddressController;
use Modules\Ecommerce\Http\Controllers\Admin\CustomerController;
use Modules\Ecommerce\Http\Controllers\Admin\DashboardController;
use Modules\Ecommerce\Http\Controllers\Admin\DiscountController;
use Modules\Ecommerce\Http\Controllers\Admin\FlashSaleController;
use Modules\Ecommerce\Http\Controllers\Admin\InvoiceController;
use Modules\Ecommerce\Http\Controllers\Admin\OrderController;
use Modules\Ecommerce\Http\Controllers\Admin\OrderReturnController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductCategoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductCollectionController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductTagController;
use Modules\Ecommerce\Http\Controllers\Admin\ReviewController;
use Modules\Ecommerce\Http\Controllers\Admin\SettingController;
use Modules\Ecommerce\Http\Controllers\Admin\ShipmentController;
use Modules\Ecommerce\Http\Controllers\Admin\ShippingController;
use Modules\Ecommerce\Http\Controllers\Admin\StoreLocatorController;
use Modules\Ecommerce\Http\Controllers\Admin\TaxController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\ForgotPasswordController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\LoginController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\RegisterController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\ResetPasswordController;
use Modules\Ecommerce\Http\Controllers\Shop\CartController;
use Modules\Ecommerce\Http\Controllers\Shop\CheckoutController;
use Modules\Ecommerce\Http\Controllers\Shop\CompareController;
use Modules\Ecommerce\Http\Controllers\Shop\ProductController as ShopProductController;
use Modules\Ecommerce\Http\Controllers\Shop\ShopController;
use Modules\Ecommerce\Http\Controllers\Shop\WishlistController;

/*
|--------------------------------------------------------------------------
| Web Routes - Ecommerce Module
|--------------------------------------------------------------------------
*/

// ===== ADMIN ROUTES =====
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('panel/ecommerce', [DashboardController::class, 'index'])->name('ecommerce.dashboard');

    // Products
    Route::prefix('panel/ecommerce/products')->name('ecommerce.products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
    });

    // Categories
    Route::prefix('panel/ecommerce/categories')->name('ecommerce.categories.')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
        Route::get('/create', [ProductCategoryController::class, 'create'])->name('create');
        Route::post('/', [ProductCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [ProductCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [ProductCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [ProductCategoryController::class, 'destroy'])->name('destroy');
    });

    // Brands
    Route::prefix('panel/ecommerce/brands')->name('ecommerce.brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::get('/create', [BrandController::class, 'create'])->name('create');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::get('/{brand}/edit', [BrandController::class, 'edit'])->name('edit');
        Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
    });

    // Customers
    Route::prefix('panel/ecommerce/customers')->name('ecommerce.customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');

        // Customer Addresses
        Route::prefix('/{customer}/addresses')->name('addresses.')->group(function () {
            Route::get('/', [CustomerAddressController::class, 'index'])->name('index');
            Route::get('/create', [CustomerAddressController::class, 'create'])->name('create');
            Route::post('/', [CustomerAddressController::class, 'store'])->name('store');
            Route::get('/{address}/edit', [CustomerAddressController::class, 'edit'])->name('edit');
            Route::put('/{address}', [CustomerAddressController::class, 'update'])->name('update');
            Route::delete('/{address}', [CustomerAddressController::class, 'destroy'])->name('destroy');
        });
    });

    // Orders
    Route::prefix('panel/ecommerce/orders')->name('ecommerce.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/status', [OrderController::class, 'updateStatus'])->name('status');
    });

    // Discounts
    Route::prefix('panel/ecommerce/discounts')->name('ecommerce.discounts.')->group(function () {
        Route::get('/', [DiscountController::class, 'index'])->name('index');
        Route::get('/create', [DiscountController::class, 'create'])->name('create');
        Route::post('/', [DiscountController::class, 'store'])->name('store');
        Route::get('/{discount}/edit', [DiscountController::class, 'edit'])->name('edit');
        Route::put('/{discount}', [DiscountController::class, 'update'])->name('update');
        Route::delete('/{discount}', [DiscountController::class, 'destroy'])->name('destroy');
    });

    // Reviews
    Route::prefix('panel/ecommerce/reviews')->name('ecommerce.reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });

    // Tags
    Route::prefix('panel/ecommerce/tags')->name('ecommerce.tags.')->group(function () {
        Route::get('/', [ProductTagController::class, 'index'])->name('index');
        Route::get('/create', [ProductTagController::class, 'create'])->name('create');
        Route::post('/', [ProductTagController::class, 'store'])->name('store');
        Route::get('/{tag}/edit', [ProductTagController::class, 'edit'])->name('edit');
        Route::put('/{tag}', [ProductTagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [ProductTagController::class, 'destroy'])->name('destroy');
    });

    // Collections
    Route::prefix('panel/ecommerce/collections')->name('ecommerce.collections.')->group(function () {
        Route::get('/', [ProductCollectionController::class, 'index'])->name('index');
        Route::get('/create', [ProductCollectionController::class, 'create'])->name('create');
        Route::post('/', [ProductCollectionController::class, 'store'])->name('store');
        Route::get('/{collection}/edit', [ProductCollectionController::class, 'edit'])->name('edit');
        Route::put('/{collection}', [ProductCollectionController::class, 'update'])->name('update');
        Route::delete('/{collection}', [ProductCollectionController::class, 'destroy'])->name('destroy');
    });

    // Flash Sales
    Route::prefix('panel/ecommerce/flash-sales')->name('ecommerce.flash-sales.')->group(function () {
        Route::get('/', [FlashSaleController::class, 'index'])->name('index');
        Route::get('/create', [FlashSaleController::class, 'create'])->name('create');
        Route::post('/', [FlashSaleController::class, 'store'])->name('store');
        Route::get('/{flashSale}/edit', [FlashSaleController::class, 'edit'])->name('edit');
        Route::put('/{flashSale}', [FlashSaleController::class, 'update'])->name('update');
        Route::delete('/{flashSale}', [FlashSaleController::class, 'destroy'])->name('destroy');
    });

    // Taxes
    Route::prefix('panel/ecommerce/taxes')->name('ecommerce.taxes.')->group(function () {
        Route::get('/', [TaxController::class, 'index'])->name('index');
        Route::get('/create', [TaxController::class, 'create'])->name('create');
        Route::post('/', [TaxController::class, 'store'])->name('store');
        Route::get('/{tax}/edit', [TaxController::class, 'edit'])->name('edit');
        Route::put('/{tax}', [TaxController::class, 'update'])->name('update');
        Route::delete('/{tax}', [TaxController::class, 'destroy'])->name('destroy');
    });

    // Shipments
    Route::prefix('panel/ecommerce/shipments')->name('ecommerce.shipments.')->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('index');
        Route::get('/{shipment}', [ShipmentController::class, 'show'])->name('show');
        Route::post('/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('status');
    });

    // Invoices
    Route::prefix('panel/ecommerce/invoices')->name('ecommerce.invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    });

    // Shipping Methods
    Route::prefix('panel/ecommerce/shipping')->name('ecommerce.shipping.')->group(function () {
        Route::get('/', [ShippingController::class, 'index'])->name('index');
        Route::get('/create', [ShippingController::class, 'create'])->name('create');
        Route::post('/', [ShippingController::class, 'store'])->name('store');
        Route::get('/{shipping}/edit', [ShippingController::class, 'edit'])->name('edit');
        Route::put('/{shipping}', [ShippingController::class, 'update'])->name('update');
        Route::delete('/{shipping}', [ShippingController::class, 'destroy'])->name('destroy');
    });

    // Store Locators
    Route::prefix('panel/ecommerce/store-locators')->name('ecommerce.store-locators.')->group(function () {
        Route::get('/', [StoreLocatorController::class, 'index'])->name('index');
        Route::get('/create', [StoreLocatorController::class, 'create'])->name('create');
        Route::post('/', [StoreLocatorController::class, 'store'])->name('store');
        Route::get('/{storeLocator}/edit', [StoreLocatorController::class, 'edit'])->name('edit');
        Route::put('/{storeLocator}', [StoreLocatorController::class, 'update'])->name('update');
        Route::delete('/{storeLocator}', [StoreLocatorController::class, 'destroy'])->name('destroy');
    });

    // Returns
    Route::prefix('panel/ecommerce/returns')->name('ecommerce.returns.')->group(function () {
        Route::get('/', [OrderReturnController::class, 'index'])->name('index');
        Route::get('/{return}', [OrderReturnController::class, 'show'])->name('show');
        Route::post('/{return}/status', [OrderReturnController::class, 'updateStatus'])->name('status');
    });

    // Settings
    Route::prefix('panel/settings/ecommerce')->name('ecommerce.settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
    });
});

// ===== PUBLIC SHOP ROUTES =====
Route::prefix('tienda')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/producto/{slug}', [ShopProductController::class, 'show'])->name('product');
    Route::get('/categoria/{slug}', [ShopProductController::class, 'category'])->name('category');
    Route::get('/marca/{slug}', [ShopProductController::class, 'brand'])->name('brand');
});

// Wishlist
Route::prefix('favoritos')->name('ecommerce.wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/{product}', [WishlistController::class, 'store'])->name('store');
    Route::delete('/{wishlist}', [WishlistController::class, 'destroy'])->name('destroy');
});

// Compare
Route::prefix('comparar')->name('ecommerce.compare.')->group(function () {
    Route::get('/', [CompareController::class, 'index'])->name('index');
    Route::post('/{product}', [CompareController::class, 'store'])->name('store');
    Route::delete('/{product}', [CompareController::class, 'destroy'])->name('destroy');
    Route::post('/clear', [CompareController::class, 'clear'])->name('clear');
});

// Cart
Route::prefix('carrito')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::put('/update/{rowId}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{rowId}', [CartController::class, 'remove'])->name('remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
});

// Checkout
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
    Route::get('/exito/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    Route::get('/reintentar/{order}', [CheckoutController::class, 'retryPayment'])->name('retry');
});

// Customer Auth
Route::prefix('tienda')->name('ecommerce.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

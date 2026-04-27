<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use Modules\Ecommerce\Http\Controllers\Admin\BrandController;
use Modules\Ecommerce\Http\Controllers\Admin\BundleController;
use Modules\Ecommerce\Http\Controllers\Admin\ChangePrimaryStoreController;
use Modules\Ecommerce\Http\Controllers\Admin\CustomerAddressController;
use Modules\Ecommerce\Http\Controllers\Admin\CustomerController;
use Modules\Ecommerce\Http\Controllers\Admin\DashboardController;
use Modules\Ecommerce\Http\Controllers\Admin\DiscountController;
use Modules\Ecommerce\Http\Controllers\Admin\EmailCampaignController;
use Modules\Ecommerce\Http\Controllers\Admin\ExportOrderController;
use Modules\Ecommerce\Http\Controllers\Admin\ExportProductCategoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ExportProductController;
use Modules\Ecommerce\Http\Controllers\Admin\ExportProductInventoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ExportProductPriceController;
use Modules\Ecommerce\Http\Controllers\Admin\FlashSaleController;
use Modules\Ecommerce\Http\Controllers\Admin\GiftCardController;
use Modules\Ecommerce\Http\Controllers\Admin\GlobalOptionController;
use Modules\Ecommerce\Http\Controllers\Admin\ImportProductCategoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ImportProductController;
use Modules\Ecommerce\Http\Controllers\Admin\ImportProductInventoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ImportProductPriceController;
use Modules\Ecommerce\Http\Controllers\Admin\IncompleteOrderController;
use Modules\Ecommerce\Http\Controllers\Admin\InvoiceController;
use Modules\Ecommerce\Http\Controllers\Admin\LegalPageController as LegalPageAdminController;
use Modules\Ecommerce\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use Modules\Ecommerce\Http\Controllers\Admin\OrderController;
use Modules\Ecommerce\Http\Controllers\Admin\OrderReturnController;
use Modules\Ecommerce\Http\Controllers\Admin\PrintOrderController;
use Modules\Ecommerce\Http\Controllers\Admin\PrintShippingLabelController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductAttributeSetController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductCategoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductCollectionController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductInventoryController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductLabelController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductOptionController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductPriceController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductQuestionController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductTagController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductVariationController;
use Modules\Ecommerce\Http\Controllers\Admin\PublishedReviewController;
use Modules\Ecommerce\Http\Controllers\Admin\ReportController;
use Modules\Ecommerce\Http\Controllers\Admin\ReviewController;
use Modules\Ecommerce\Http\Controllers\Admin\ReviewReplyController;
use Modules\Ecommerce\Http\Controllers\Admin\SettingController;
use Modules\Ecommerce\Http\Controllers\Admin\ShipmentController;
use Modules\Ecommerce\Http\Controllers\Admin\ShippingController;
use Modules\Ecommerce\Http\Controllers\Admin\ShippingRuleController;
use Modules\Ecommerce\Http\Controllers\Admin\ShippingRuleItemController;
use Modules\Ecommerce\Http\Controllers\Admin\SpecificationAttributeController;
use Modules\Ecommerce\Http\Controllers\Admin\SpecificationGroupController;
use Modules\Ecommerce\Http\Controllers\Admin\SpecificationTableController;
use Modules\Ecommerce\Http\Controllers\Admin\StoreLocatorController;
use Modules\Ecommerce\Http\Controllers\Admin\StoreLocatorSettingController;
use Modules\Ecommerce\Http\Controllers\Admin\TaxController;
use Modules\Ecommerce\Http\Controllers\Admin\TaxRuleController;
use Modules\Ecommerce\Http\Controllers\Admin\UploadProofController;
use Modules\Ecommerce\Http\Controllers\Admin\WebhookLogController;
use Modules\Ecommerce\Http\Controllers\ConfirmDeliveryController;
use Modules\Ecommerce\Http\Controllers\LocaleSwitchController;
use Modules\Ecommerce\Http\Controllers\OrderTrackingController;
use Modules\Ecommerce\Http\Controllers\RobotsController;
use Modules\Ecommerce\Http\Controllers\Shop\AffiliateController as ShopAffiliateController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\EmailVerificationController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\ForgotPasswordController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\LoginController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\RegisterController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\ResetPasswordController;
use Modules\Ecommerce\Http\Controllers\Shop\Auth\SocialAuthController;
use Modules\Ecommerce\Http\Controllers\Shop\CartController;
use Modules\Ecommerce\Http\Controllers\Shop\ChatbotController;
use Modules\Ecommerce\Http\Controllers\Shop\CheckoutController;
use Modules\Ecommerce\Http\Controllers\Shop\CompareController;
use Modules\Ecommerce\Http\Controllers\Shop\CustomerAccountController;
use Modules\Ecommerce\Http\Controllers\Shop\GdprExportController;
use Modules\Ecommerce\Http\Controllers\Shop\LegalPageController;
use Modules\Ecommerce\Http\Controllers\Shop\NewsletterController as ShopNewsletterController;
use Modules\Ecommerce\Http\Controllers\Shop\ProductController as ShopProductController;
use Modules\Ecommerce\Http\Controllers\Shop\RestockAlertController;
use Modules\Ecommerce\Http\Controllers\Shop\SavedSearchController;
use Modules\Ecommerce\Http\Controllers\Shop\ShopController;
use Modules\Ecommerce\Http\Controllers\Shop\SubscriptionController;
use Modules\Ecommerce\Http\Controllers\Shop\WishlistController;
use Modules\Ecommerce\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| Web Routes - Ecommerce Module
|--------------------------------------------------------------------------
*/

// ===== ADMIN ROUTES =====
Route::middleware(['web', 'auth'])->group(function () {

    // Dashboard
    Route::get('panel/ecommerce', [DashboardController::class, 'index'])->name('ecommerce.dashboard');

    // Reports
    Route::get('panel/ecommerce/reports', [ReportController::class, 'index'])->name('ecommerce.reports.index');
    Route::get('panel/ecommerce/reports/comparison', [ReportController::class, 'comparison'])->name('ecommerce.reports.comparison');
    Route::get('panel/ecommerce/reports/funnel', [ReportController::class, 'funnel'])->name('ecommerce.reports.funnel');
    Route::get('panel/ecommerce/reports/customer-ltv', [ReportController::class, 'customerLtv'])->name('ecommerce.reports.customer-ltv');
    Route::get('panel/ecommerce/reports/abandoned-carts', [ReportController::class, 'abandonedCarts'])->name('ecommerce.reports.abandoned-carts');
    Route::get('panel/ecommerce/reports/search', [ReportController::class, 'searchAnalytics'])->name('ecommerce.reports.search');
    Route::get('panel/ecommerce/reports/margin', [ReportController::class, 'marginAnalysis'])->name('ecommerce.reports.margin');
    Route::get('panel/ecommerce/reports/forecast', [ReportController::class, 'demandForecast'])->name('ecommerce.reports.forecast');
    Route::get('panel/ecommerce/reports/journey', [ReportController::class, 'customerJourney'])->name('ecommerce.reports.journey');

    // Incomplete Orders
    Route::get('panel/ecommerce/incomplete-orders', [IncompleteOrderController::class, 'index'])->name('ecommerce.incomplete-orders.index');
    Route::get('panel/ecommerce/incomplete-orders/{order}', [IncompleteOrderController::class, 'show'])->name('ecommerce.incomplete-orders.show');
    Route::post('panel/ecommerce/incomplete-orders/{order}/complete', [IncompleteOrderController::class, 'markComplete'])->name('ecommerce.incomplete-orders.complete');
    Route::post('panel/ecommerce/incomplete-orders/{order}/note', [IncompleteOrderController::class, 'saveNote'])->name('ecommerce.incomplete-orders.note');
    Route::post('panel/ecommerce/incomplete-orders/{order}/send-recovery', [IncompleteOrderController::class, 'sendRecoveryEmail'])->name('ecommerce.incomplete-orders.send-recovery');

    // Product search (must be before the prefix group to avoid /{product} collision)
    Route::get('panel/ecommerce/products/search', [ProductController::class, 'searchProducts'])->name('ecommerce.products.search');

    // Quick search (before the products prefix group)
    Route::get('panel/ecommerce/quick-search', [DashboardController::class, 'quickSearch'])->name('ecommerce.quick-search');

    // Products
    Route::prefix('panel/ecommerce/products')->name('ecommerce.products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
        Route::post('/bulk-action', [ProductController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/inline-update', [ProductController::class, 'inlineUpdate'])->name('inline-update');
        Route::post('/{product}/generate-description', [ProductController::class, 'generateDescription'])->name('generate-description');
        Route::get('/export', ExportProductController::class)->name('export');
        Route::get('/import', [ImportProductController::class, 'showForm'])->name('import');
        Route::post('/import', [ImportProductController::class, 'import'])->name('import.store');
        Route::post('/{product}/variations', [ProductVariationController::class, 'store'])->name('variations.store');
        Route::post('/{product}/variations/{variation}/default', [ProductVariationController::class, 'setDefault'])->name('variations.default');
        Route::delete('/{product}/variations/{variation}', [ProductVariationController::class, 'destroy'])->name('variations.destroy');
    });

    // Categories Export + Import (before prefix group to avoid /{category} collision)
    Route::get('panel/ecommerce/categories/export', ExportProductCategoryController::class)->name('ecommerce.categories.export');
    Route::get('panel/ecommerce/categories/import', [ImportProductCategoryController::class, 'create'])->name('ecommerce.categories.import');
    Route::post('panel/ecommerce/categories/import', [ImportProductCategoryController::class, 'store'])->name('ecommerce.categories.import.store');

    // Categories
    Route::prefix('panel/ecommerce/categories')->name('ecommerce.categories.')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
        Route::get('/create', [ProductCategoryController::class, 'create'])->name('create');
        Route::post('/', [ProductCategoryController::class, 'store'])->name('store');
        Route::post('/reorder', [ProductCategoryController::class, 'reorder'])->name('reorder');
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

    // Orders Export + PDF (must be before the orders prefix group to avoid /{order} collision)
    Route::get('panel/ecommerce/orders/export', ExportOrderController::class)->name('ecommerce.orders.export');
    Route::get('panel/ecommerce/orders/{order}/pdf', PrintOrderController::class)->name('ecommerce.orders.pdf');

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
        Route::post('/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('confirm-payment');
        Route::post('/{order}/cancel', [OrderController::class, 'cancelOrder'])->name('cancel');
        Route::post('/{order}/note', [OrderController::class, 'addNote'])->name('note');
        Route::post('/{order}/shipment', [OrderController::class, 'createShipment'])->name('shipment');
        Route::get('/{order}/reorder', [OrderController::class, 'reorder'])->name('reorder');
        Route::get('/{order}/manage', [OrderController::class, 'manage'])->name('manage');
        Route::post('/{order}/manage', [OrderController::class, 'saveManage'])->name('manage.save');
        Route::post('/{order}/apply-coupon', [OrderController::class, 'applyDiscountCode'])->name('apply-coupon');
        Route::post('/{order}/customer-note', [OrderController::class, 'updateCustomerNote'])->name('customer-note');
        Route::post('/{order}/shipping-address', [OrderController::class, 'updateShippingAddress'])->name('shipping-address');
        Route::patch('/{order}/delivery', [OrderController::class, 'updateDelivery'])->name('delivery');
        Route::post('/{order}/resend-confirmation', [OrderController::class, 'resendConfirmation'])->name('resend-confirmation');
    });

    // Order utilities (discount + shipping calculation)
    Route::post('panel/ecommerce/discount/calculate', [OrderController::class, 'calculateDiscount'])->name('ecommerce.discount.calculate');
    Route::get('panel/ecommerce/shipping-options', [OrderController::class, 'getShippingOptions'])->name('ecommerce.shipping.options');

    // Discounts
    Route::prefix('panel/ecommerce/discounts')->name('ecommerce.discounts.')->group(function () {
        Route::get('/', [DiscountController::class, 'index'])->name('index');
        Route::get('/create', [DiscountController::class, 'create'])->name('create');
        Route::post('/', [DiscountController::class, 'store'])->name('store');
        Route::post('/generate-code', [DiscountController::class, 'generateCode'])->name('generate-code');
        Route::get('/{discount}/edit', [DiscountController::class, 'edit'])->name('edit');
        Route::put('/{discount}', [DiscountController::class, 'update'])->name('update');
        Route::delete('/{discount}', [DiscountController::class, 'destroy'])->name('destroy');
    });

    // Reviews
    Route::prefix('panel/ecommerce/reviews')->name('ecommerce.reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
        Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
        Route::put('/{review}/reply', [ReviewController::class, 'reply'])->name('reply');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
        Route::post('/{review}/toggle-publish', PublishedReviewController::class)->name('toggle-publish');
    });

    // Review Replies (JSON)
    Route::prefix('panel/ecommerce/reviews')->name('ecommerce.reviews.reply.')->group(function () {
        Route::post('/{review}/reply', [ReviewReplyController::class, 'store'])->name('store');
        Route::put('/{review}/reply/update', [ReviewReplyController::class, 'update'])->name('update');
        Route::delete('/{review}/reply', [ReviewReplyController::class, 'destroy'])->name('destroy');
    });

    // Product Questions (admin)
    Route::prefix('panel/ecommerce/product-questions')->name('ecommerce.product-questions.')->group(function () {
        Route::get('/', [ProductQuestionController::class, 'index'])->name('index');
        Route::post('/{question}/answer', [ProductQuestionController::class, 'answer'])->name('answer');
        Route::delete('/{question}', [ProductQuestionController::class, 'destroy'])->name('destroy');
    });

    // Upload Payment Proof
    Route::post('panel/ecommerce/orders/{order}/upload-proof', UploadProofController::class)->name('ecommerce.orders.upload-proof');

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

        // Tax Rules (nested under taxes)
        Route::prefix('/{tax}/rules')->name('rules.')->group(function () {
            Route::post('/', [TaxRuleController::class, 'store'])->name('store');
            Route::put('/{taxRule}', [TaxRuleController::class, 'update'])->name('update');
            Route::delete('/{taxRule}', [TaxRuleController::class, 'destroy'])->name('destroy');
        });
    });

    // Shipments
    Route::prefix('panel/ecommerce/shipments')->name('ecommerce.shipments.')->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('index');
        Route::get('/{shipment}/edit', [ShipmentController::class, 'edit'])->name('edit');
        Route::put('/{shipment}', [ShipmentController::class, 'update'])->name('update');
        Route::get('/{shipment}', [ShipmentController::class, 'show'])->name('show');
        Route::post('/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('status');
        Route::post('/{shipment}/history', [ShipmentController::class, 'addHistory'])->name('history.store');
        Route::get('/{shipment}/label', PrintShippingLabelController::class)->name('label');
        Route::post('/{shipment}/token', [ShipmentController::class, 'generateDeliveryToken'])->name('token');
    });

    // Invoices
    Route::prefix('panel/ecommerce/invoices')->name('ecommerce.invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
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

        // Toggle status
        Route::post('/{shipping}/toggle-status', [ShippingController::class, 'toggleStatus'])->name('toggle-status');

        // Shipping Rules (nested under shipping)
        Route::prefix('/{shipping}/rules')->name('rules.')->group(function () {
            Route::post('/', [ShippingRuleController::class, 'store'])->name('store');
            Route::put('/{rule}', [ShippingRuleController::class, 'update'])->name('update');
            Route::delete('/{rule}', [ShippingRuleController::class, 'destroy'])->name('destroy');

            // Shipping Rule Items (nested under rules)
            Route::prefix('/{rule}/items')->name('items.')->group(function () {
                Route::get('/', [ShippingRuleItemController::class, 'index'])->name('index');
                Route::post('/', [ShippingRuleItemController::class, 'store'])->name('store');
                Route::put('/{item}', [ShippingRuleItemController::class, 'update'])->name('update');
                Route::delete('/{item}', [ShippingRuleItemController::class, 'destroy'])->name('destroy');
            });
        });
    });

    // Inventory Export + Import (before prefix group to avoid /{product} collision)
    Route::get('panel/ecommerce/inventory/export', ExportProductInventoryController::class)->name('ecommerce.inventory.export');
    Route::get('panel/ecommerce/inventory/import', [ImportProductInventoryController::class, 'create'])->name('ecommerce.inventory.import');
    Route::post('panel/ecommerce/inventory/import', [ImportProductInventoryController::class, 'store'])->name('ecommerce.inventory.import.store');

    // Inventory
    Route::prefix('panel/ecommerce/inventory')->name('ecommerce.inventory.')->group(function () {
        Route::get('/', [ProductInventoryController::class, 'index'])->name('index');
        Route::patch('/{product}', [ProductInventoryController::class, 'update'])->name('update');
        Route::post('/bulk', [ProductInventoryController::class, 'bulkUpdate'])->name('bulk');
    });

    // Product Prices Export + Import (before prefix group to avoid /{product} collision)
    Route::get('panel/ecommerce/product-prices/export', ExportProductPriceController::class)->name('ecommerce.product-prices.export');
    Route::get('panel/ecommerce/product-prices/import', [ImportProductPriceController::class, 'create'])->name('ecommerce.product-prices.import');
    Route::post('panel/ecommerce/product-prices/import', [ImportProductPriceController::class, 'store'])->name('ecommerce.product-prices.import.store');

    // Product Prices
    Route::prefix('panel/ecommerce/product-prices')->name('ecommerce.product-prices.')->group(function () {
        Route::get('/', [ProductPriceController::class, 'index'])->name('index');
        Route::patch('/{product}', [ProductPriceController::class, 'update'])->name('update');
        Route::post('/bulk', [ProductPriceController::class, 'bulkUpdate'])->name('bulk');
    });

    // Store Locators
    Route::prefix('panel/ecommerce/store-locators')->name('ecommerce.store-locators.')->group(function () {
        Route::get('/', [StoreLocatorController::class, 'index'])->name('index');
        Route::get('/create', [StoreLocatorController::class, 'create'])->name('create');
        Route::post('/', [StoreLocatorController::class, 'store'])->name('store');
        Route::get('/{storeLocator}/edit', [StoreLocatorController::class, 'edit'])->name('edit');
        Route::put('/{storeLocator}', [StoreLocatorController::class, 'update'])->name('update');
        Route::delete('/{storeLocator}', [StoreLocatorController::class, 'destroy'])->name('destroy');
        Route::post('/{storeLocator}/set-primary', ChangePrimaryStoreController::class)->name('set-primary');
    });

    // Returns
    Route::prefix('panel/ecommerce/returns')->name('ecommerce.returns.')->group(function () {
        Route::get('/', [OrderReturnController::class, 'index'])->name('index');
        Route::get('/{return}', [OrderReturnController::class, 'show'])->name('show');
        Route::get('/{return}/edit', [OrderReturnController::class, 'edit'])->name('edit');
        Route::put('/{return}', [OrderReturnController::class, 'update'])->name('update');
        Route::post('/{return}/status', [OrderReturnController::class, 'updateStatus'])->name('status');
        Route::delete('/{return}', [OrderReturnController::class, 'destroy'])->name('destroy');
    });

    // Product Labels
    Route::prefix('panel/ecommerce/product-labels')->name('ecommerce.product-labels.')->group(function () {
        Route::get('/', [ProductLabelController::class, 'index'])->name('index');
        Route::get('/create', [ProductLabelController::class, 'create'])->name('create');
        Route::post('/', [ProductLabelController::class, 'store'])->name('store');
        Route::get('/{label}/edit', [ProductLabelController::class, 'edit'])->name('edit');
        Route::put('/{label}', [ProductLabelController::class, 'update'])->name('update');
        Route::delete('/{label}', [ProductLabelController::class, 'destroy'])->name('destroy');
    });

    // Global Options
    Route::prefix('panel/ecommerce/global-options')->name('ecommerce.global-options.')->group(function () {
        Route::get('/', [GlobalOptionController::class, 'index'])->name('index');
        Route::get('/create', [GlobalOptionController::class, 'create'])->name('create');
        Route::post('/', [GlobalOptionController::class, 'store'])->name('store');
        Route::get('/{globalOption}/edit', [GlobalOptionController::class, 'edit'])->name('edit');
        Route::put('/{globalOption}', [GlobalOptionController::class, 'update'])->name('update');
        Route::delete('/{globalOption}', [GlobalOptionController::class, 'destroy'])->name('destroy');
    });

    // Product Attribute Sets
    Route::prefix('panel/ecommerce/attribute-sets')->name('ecommerce.product-attribute-sets.')->group(function () {
        Route::get('/', [ProductAttributeSetController::class, 'index'])->name('index');
        Route::get('/create', [ProductAttributeSetController::class, 'create'])->name('create');
        Route::post('/', [ProductAttributeSetController::class, 'store'])->name('store');
        Route::get('/{productAttributeSet}/edit', [ProductAttributeSetController::class, 'edit'])->name('edit');
        Route::put('/{productAttributeSet}', [ProductAttributeSetController::class, 'update'])->name('update');
        Route::delete('/{productAttributeSet}', [ProductAttributeSetController::class, 'destroy'])->name('destroy');
    });

    // Product Options
    Route::prefix('panel/ecommerce/product-options')->name('ecommerce.product-options.')->group(function () {
        Route::get('/', [ProductOptionController::class, 'index'])->name('index');
        Route::get('/create', [ProductOptionController::class, 'create'])->name('create');
        Route::post('/', [ProductOptionController::class, 'store'])->name('store');
        Route::get('/{option}/edit', [ProductOptionController::class, 'edit'])->name('edit');
        Route::put('/{option}', [ProductOptionController::class, 'update'])->name('update');
        Route::delete('/{option}', [ProductOptionController::class, 'destroy'])->name('destroy');
    });

    // Specification Groups
    Route::prefix('panel/ecommerce/specification-groups')->name('ecommerce.specification-groups.')->group(function () {
        Route::get('/', [SpecificationGroupController::class, 'index'])->name('index');
        Route::get('/create', [SpecificationGroupController::class, 'create'])->name('create');
        Route::post('/', [SpecificationGroupController::class, 'store'])->name('store');
        Route::get('/{specificationGroup}/edit', [SpecificationGroupController::class, 'edit'])->name('edit');
        Route::put('/{specificationGroup}', [SpecificationGroupController::class, 'update'])->name('update');
        Route::delete('/{specificationGroup}', [SpecificationGroupController::class, 'destroy'])->name('destroy');
    });

    // Specification Attributes
    Route::prefix('panel/ecommerce/specification-attributes')->name('ecommerce.specification-attributes.')->group(function () {
        Route::get('/', [SpecificationAttributeController::class, 'index'])->name('index');
        Route::get('/create', [SpecificationAttributeController::class, 'create'])->name('create');
        Route::post('/', [SpecificationAttributeController::class, 'store'])->name('store');
        Route::get('/{specificationAttribute}/edit', [SpecificationAttributeController::class, 'edit'])->name('edit');
        Route::put('/{specificationAttribute}', [SpecificationAttributeController::class, 'update'])->name('update');
        Route::delete('/{specificationAttribute}', [SpecificationAttributeController::class, 'destroy'])->name('destroy');
    });

    // Specification Tables
    Route::prefix('panel/ecommerce/specification-tables')->name('ecommerce.specification-tables.')->group(function () {
        Route::get('/', [SpecificationTableController::class, 'index'])->name('index');
        Route::get('/create', [SpecificationTableController::class, 'create'])->name('create');
        Route::post('/', [SpecificationTableController::class, 'store'])->name('store');
        Route::get('/{specificationTable}/edit', [SpecificationTableController::class, 'edit'])->name('edit');
        Route::put('/{specificationTable}', [SpecificationTableController::class, 'update'])->name('update');
        Route::delete('/{specificationTable}', [SpecificationTableController::class, 'destroy'])->name('destroy');
    });

    // Newsletter (admin)
    Route::get('panel/ecommerce/newsletter/export', [AdminNewsletterController::class, 'export'])->name('ecommerce.newsletter.export');
    Route::prefix('panel/ecommerce/newsletter')->name('ecommerce.newsletter.')->group(function () {
        Route::get('/', [AdminNewsletterController::class, 'index'])->name('index');
        Route::delete('/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [AdminNewsletterController::class, 'bulkAction'])->name('bulk-action');
    });

    // Email campaigns
    Route::prefix('panel/ecommerce/email-campaigns')->name('ecommerce.email-campaigns.')->group(function () {
        Route::get('/', [EmailCampaignController::class, 'index'])->name('index');
        Route::get('/create', [EmailCampaignController::class, 'create'])->name('create');
        Route::post('/', [EmailCampaignController::class, 'store'])->name('store');
        Route::post('/{campaign}/send', [EmailCampaignController::class, 'send'])->name('send');
        Route::delete('/{campaign}', [EmailCampaignController::class, 'destroy'])->name('destroy');
    });

    // Legal Pages
    Route::prefix('panel/ecommerce/legal-pages')->name('ecommerce.legal-pages.')->group(function () {
        Route::get('/', [LegalPageAdminController::class, 'index'])->name('index');
        Route::get('/create', [LegalPageAdminController::class, 'create'])->name('create');
        Route::post('/', [LegalPageAdminController::class, 'store'])->name('store');
        Route::get('/{legal_page}/edit', [LegalPageAdminController::class, 'edit'])->name('edit');
        Route::put('/{legal_page}', [LegalPageAdminController::class, 'update'])->name('update');
        Route::delete('/{legal_page}', [LegalPageAdminController::class, 'destroy'])->name('destroy');
    });

    // Webhook logs
    Route::prefix('panel/ecommerce/webhook-logs')->name('ecommerce.webhook-logs.')->group(function () {
        Route::get('/', [WebhookLogController::class, 'index'])->name('index');
        Route::get('/{webhook_log}', [WebhookLogController::class, 'show'])->name('show');
    });

    // Bundles
    Route::prefix('panel/ecommerce/bundles')->name('ecommerce.bundles.')->group(function () {
        Route::get('/', [BundleController::class, 'index'])->name('index');
        Route::get('/create', [BundleController::class, 'create'])->name('create');
        Route::post('/', [BundleController::class, 'store'])->name('store');
        Route::get('/{bundle}/edit', [BundleController::class, 'edit'])->name('edit');
        Route::put('/{bundle}', [BundleController::class, 'update'])->name('update');
        Route::delete('/{bundle}', [BundleController::class, 'destroy'])->name('destroy');
    });

    // Gift Cards
    Route::prefix('panel/ecommerce/gift-cards')->name('ecommerce.gift-cards.')->group(function () {
        Route::get('/', [GiftCardController::class, 'index'])->name('index');
        Route::get('/create', [GiftCardController::class, 'create'])->name('create');
        Route::post('/', [GiftCardController::class, 'store'])->name('store');
        Route::get('/{giftCard}', [GiftCardController::class, 'show'])->name('show');
        Route::delete('/{giftCard}', [GiftCardController::class, 'destroy'])->name('destroy');
    });

    // Affiliates
    Route::prefix('panel/ecommerce/affiliates')->name('ecommerce.affiliates.')->group(function () {
        Route::get('/', [AdminAffiliateController::class, 'index'])->name('index');
        Route::get('/{affiliate}', [AdminAffiliateController::class, 'show'])->name('show');
        Route::put('/{affiliate}', [AdminAffiliateController::class, 'update'])->name('update');
        Route::post('/referrals/{referral}/approve', [AdminAffiliateController::class, 'approveReferral'])->name('approve-referral');
        Route::post('/{affiliate}/mark-paid', [AdminAffiliateController::class, 'markPaid'])->name('mark-paid');
    });

    // Settings
    Route::prefix('panel/settings/ecommerce')->name('settings.ecommerce.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::patch('/', [SettingController::class, 'update'])->name('update');

        Route::get('/currencies', [SettingController::class, 'currencies'])->name('currencies.index');
        Route::patch('/currencies', [SettingController::class, 'updateCurrencies'])->name('currencies.update');

        Route::get('/products', [SettingController::class, 'products'])->name('products.index');
        Route::patch('/products', [SettingController::class, 'updateProducts'])->name('products.update');

        Route::get('/product-search', [SettingController::class, 'productSearch'])->name('product-search.index');
        Route::patch('/product-search', [SettingController::class, 'updateProductSearch'])->name('product-search.update');

        Route::get('/digital-products', [SettingController::class, 'digitalProducts'])->name('digital-products.index');
        Route::patch('/digital-products', [SettingController::class, 'updateDigitalProducts'])->name('digital-products.update');

        Route::get('/product-reviews', [SettingController::class, 'productReviews'])->name('product-reviews.index');
        Route::patch('/product-reviews', [SettingController::class, 'updateProductReviews'])->name('product-reviews.update');

        Route::get('/shopping', [SettingController::class, 'shopping'])->name('shopping.index');
        Route::patch('/shopping', [SettingController::class, 'updateShopping'])->name('shopping.update');

        Route::get('/checkout', [SettingController::class, 'checkout'])->name('checkout.index');
        Route::patch('/checkout', [SettingController::class, 'updateCheckout'])->name('checkout.update');

        Route::get('/return', [SettingController::class, 'returnSettings'])->name('return.index');
        Route::patch('/return', [SettingController::class, 'updateReturn'])->name('return.update');

        Route::get('/invoices', [SettingController::class, 'invoices'])->name('invoices.index');
        Route::patch('/invoices', [SettingController::class, 'updateInvoices'])->name('invoices.update');

        Route::get('/invoice-template', [SettingController::class, 'invoiceTemplate'])->name('invoice-template.index');
        Route::patch('/invoice-template', [SettingController::class, 'updateInvoiceTemplate'])->name('invoice-template.update');
        Route::get('/invoice-template/order/preview', [SettingController::class, 'previewInvoiceTemplate'])->name('invoice-template.order.preview');

        Route::get('/shipping-label-template', [SettingController::class, 'shippingLabelTemplate'])->name('shipping-label-template.index');
        Route::patch('/shipping-label-template', [SettingController::class, 'updateShippingLabelTemplate'])->name('shipping-label-template.update');
        Route::get('/shipping-label-template/preview', [SettingController::class, 'previewShippingLabelTemplate'])->name('shipping-label-template.preview');

        Route::get('/taxes', [SettingController::class, 'taxesSettings'])->name('taxes.index');
        Route::patch('/taxes', [SettingController::class, 'updateTaxes'])->name('taxes.update');

        Route::get('/customers', [SettingController::class, 'customers'])->name('customers.index');
        Route::patch('/customers', [SettingController::class, 'updateCustomers'])->name('customers.update');

        Route::get('/shipping', [SettingController::class, 'shippingSettings'])->name('shipping.index');
        Route::patch('/shipping', [SettingController::class, 'updateShippingSettings'])->name('shipping.update');

        Route::get('/webhook', [SettingController::class, 'webhook'])->name('webhook.index');
        Route::patch('/webhook', [SettingController::class, 'updateWebhook'])->name('webhook.update');

        Route::get('/tracking', [SettingController::class, 'tracking'])->name('tracking.index');
        Route::patch('/tracking', [SettingController::class, 'updateTracking'])->name('tracking.update');

        Route::get('/standard-and-format', [SettingController::class, 'standardAndFormat'])->name('standard-and-format.index');
        Route::patch('/standard-and-format', [SettingController::class, 'updateStandardAndFormat'])->name('standard-and-format.update');

        Route::get('/flash-sale', [SettingController::class, 'flashSale'])->name('flash-sale.index');
        Route::patch('/flash-sale', [SettingController::class, 'updateFlashSale'])->name('flash-sale.update');

        Route::get('/abandoned-cart', [SettingController::class, 'abandonedCart'])->name('abandoned-cart.index');
        Route::patch('/abandoned-cart', [SettingController::class, 'updateAbandonedCart'])->name('abandoned-cart.update');

        Route::prefix('/store-locators')->name('store-locators.')->group(function () {
            Route::get('/', [StoreLocatorSettingController::class, 'index'])->name('index');
            Route::get('/create', [StoreLocatorSettingController::class, 'create'])->name('create');
            Route::post('/', [StoreLocatorSettingController::class, 'store'])->name('store');
            Route::get('/{storeLocator}/edit', [StoreLocatorSettingController::class, 'edit'])->name('edit');
            Route::put('/{storeLocator}', [StoreLocatorSettingController::class, 'update'])->name('update');
            Route::delete('/{storeLocator}', [StoreLocatorSettingController::class, 'destroy'])->name('destroy');
        });
    });
});

// ===== SEO ROUTES =====
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('shop.sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('shop.robots');

// ===== PUBLIC SHOP ROUTES =====
Route::middleware(['web', 'throttle:60,1'])->prefix('tienda')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/producto/{slug}', [ShopProductController::class, 'show'])->name('product');
    Route::get('/producto/{id}/quick', [ShopProductController::class, 'quick'])->where('id', '[0-9]+')->name('product.quick');
    Route::get('/categoria/{slug}', [ShopProductController::class, 'category'])->name('category');
    Route::get('/marca/{slug}', [ShopProductController::class, 'brand'])->name('brand');
    Route::post('/newsletter/subscribe', [ShopNewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,1');
    Route::get('/newsletter/unsubscribe/{token}', [ShopNewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    Route::post('/producto/{product}/restock-alert', [RestockAlertController::class, 'store'])->name('products.restock-alert')->middleware('throttle:5,1');
    Route::post('/producto/{product}/pregunta', [Modules\Ecommerce\Http\Controllers\Shop\ProductQuestionController::class, 'store'])->name('product.question')->middleware('throttle:5,1');
});

// Bundles (public)
Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/tienda/bundles', [Modules\Ecommerce\Http\Controllers\Shop\BundleController::class, 'index'])->name('shop.bundles.index');
    Route::get('/tienda/bundle/{slug}', [Modules\Ecommerce\Http\Controllers\Shop\BundleController::class, 'show'])->name('shop.bundles.show');
});

Route::middleware(['web', 'throttle:30,1'])->group(function () {
    Route::post('/tienda/bundle/{bundle}/add-to-cart', [Modules\Ecommerce\Http\Controllers\Shop\BundleController::class, 'addToCart'])->name('shop.bundles.add-to-cart');
});

// Gift Cards (public)
Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/tienda/gift-cards', [Modules\Ecommerce\Http\Controllers\Shop\GiftCardController::class, 'show'])->name('shop.gift-cards');
});

Route::middleware(['web', 'throttle:30,1'])->group(function () {
    Route::post('/tienda/gift-cards/purchase', [Modules\Ecommerce\Http\Controllers\Shop\GiftCardController::class, 'purchase'])->name('shop.gift-cards.purchase');
    Route::post('/tienda/gift-cards/redeem', [Modules\Ecommerce\Http\Controllers\Shop\GiftCardController::class, 'redeem'])->name('shop.gift-cards.redeem');
});

// Legal pages (public)
Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/legal/{slug}', [LegalPageController::class, 'show'])->name('shop.legal.show');
});

// Chatbot (public with rate limit)
Route::middleware(['web', 'throttle:30,1'])->group(function () {
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('shop.chatbot.ask');
});

// Wishlist
Route::middleware(['web', 'throttle:30,1'])->prefix('favoritos')->name('ecommerce.wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::get('/share-url', [WishlistController::class, 'getShareUrl'])->name('share-url');
    Route::post('/{product}', [WishlistController::class, 'store'])->name('store');
    Route::delete('/{wishlist}', [WishlistController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'throttle:30,1'])->group(function () {
    Route::get('/favoritos/compartido/{token}', [WishlistController::class, 'shared'])->name('shop.wishlist.shared');
    Route::get('/locale/{locale}', [LocaleSwitchController::class, 'switch'])->name('locale.switch');
});

// Compare
Route::middleware(['web', 'throttle:30,1'])->prefix('comparar')->name('ecommerce.compare.')->group(function () {
    Route::get('/', [CompareController::class, 'index'])->name('index');
    Route::post('/{product}', [CompareController::class, 'store'])->name('store');
    Route::delete('/{product}', [CompareController::class, 'destroy'])->name('destroy');
    Route::post('/clear', [CompareController::class, 'clear'])->name('clear');
});

// Cart
Route::middleware(['web', 'throttle:30,1'])->prefix('carrito')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::put('/update/{rowId}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{rowId}', [CartController::class, 'remove'])->name('remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
});

// Checkout
Route::middleware(['web', 'throttle:30,1'])->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->middleware('idempotency')->name('store');
    Route::get('/exito/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    Route::get('/reintentar/{order}', [CheckoutController::class, 'retryPayment'])->name('retry');
});

// Order Tracking (public)
Route::middleware(['web', 'throttle:10,1'])->prefix('seguimiento')->name('ecommerce.tracking.')->group(function () {
    Route::get('/', [OrderTrackingController::class, 'show'])->name('index');
    Route::post('/', [OrderTrackingController::class, 'search'])->name('search');
});

// Delivery Confirmation (public, token-based)
Route::middleware(['web', 'throttle:10,1'])->prefix('delivery')->name('ecommerce.delivery.')->group(function () {
    Route::get('/confirm/{token}', [ConfirmDeliveryController::class, 'show'])->name('confirm');
    Route::post('/confirm/{token}', [ConfirmDeliveryController::class, 'confirm'])->name('confirm.post');
    Route::get('/confirmed/{token}', [ConfirmDeliveryController::class, 'confirmed'])->name('confirmed');
});

// Customer Auth
Route::middleware(['web', 'throttle:10,1'])->prefix('tienda')->name('ecommerce.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::get('/confirm-email', [EmailVerificationController::class, 'verify'])->name('email.verify');
    Route::post('/resend-verification', [EmailVerificationController::class, 'resend'])
        ->middleware('auth:ecommerce')
        ->name('email.resend');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'google|facebook')
        ->name('social.redirect');

    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google|facebook')
        ->name('social.callback');
});

// Customer account area (requires ecommerce auth)
Route::middleware(['web', 'auth:ecommerce'])
    ->prefix('tienda/cuenta')
    ->name('account.')
    ->group(function () {
        Route::get('/', [CustomerAccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/ordenes', [CustomerAccountController::class, 'orders'])->name('orders');
        Route::get('/ordenes/{order}', [CustomerAccountController::class, 'orderDetail'])->name('order-detail');
        Route::post('/ordenes/{order}/reorder', [CustomerAccountController::class, 'reorder'])->name('order.reorder');
        Route::get('/perfil', [CustomerAccountController::class, 'profile'])->name('profile');
        Route::put('/perfil', [CustomerAccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/contrasena', [CustomerAccountController::class, 'updatePassword'])->name('password.update');
        Route::get('/direcciones', [CustomerAccountController::class, 'addresses'])->name('addresses');
        Route::post('/direcciones', [CustomerAccountController::class, 'storeAddress'])->name('addresses.store');
        Route::delete('/direcciones/{address}', [CustomerAccountController::class, 'destroyAddress'])->name('addresses.destroy');
        Route::post('/direcciones/{address}/default', [CustomerAccountController::class, 'setDefaultAddress'])->name('addresses.default');
        Route::post('/gdpr-export', [GdprExportController::class, 'request'])->name('gdpr.request');

        Route::prefix('referidos')->name('affiliate.')->group(function () {
            Route::get('/', [ShopAffiliateController::class, 'dashboard'])->name('dashboard');
            Route::post('/enroll', [ShopAffiliateController::class, 'enroll'])->name('enroll');
        });

        Route::prefix('busquedas-guardadas')->name('saved-searches.')->group(function () {
            Route::get('/', [SavedSearchController::class, 'index'])->name('index');
            Route::post('/', [SavedSearchController::class, 'store'])->name('store');
            Route::post('/{savedSearch}/toggle', [SavedSearchController::class, 'toggle'])->name('toggle');
            Route::delete('/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('suscripciones')->name('subscriptions.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('index');
            Route::post('/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('pause');
            Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
            Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        });
    });

Route::middleware(['web', 'auth:ecommerce'])->group(function () {
    Route::post('/tienda/producto/{product}/subscribe', [SubscriptionController::class, 'subscribe'])->name('shop.product.subscribe');
});

// GDPR data export download (public, signed URL)
Route::middleware(['web', 'throttle:10,1'])->group(function () {
    Route::get('/gdpr-download/{token}/{customer}', [GdprExportController::class, 'download'])->name('shop.gdpr.download');
});

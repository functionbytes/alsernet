<?php

use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;
use Theme\Wowy\Http\Controllers\WowyController;

Theme::registerRoutes(function (): void {
    Route::group(['prefix' => 'ajax', 'as' => 'public.ajax.', 'controller' => WowyController::class], function (): void {
        Route::get('cart', 'ajaxCart')
            ->name('cart');

        Route::get('quick-view/{id}', 'getQuickView')
            ->name('quick-view')
            ->wherePrimaryKey();

        Route::get('products-by-collection/{id}', 'ajaxGetProductsByCollection')
            ->name('products-by-collection')
            ->wherePrimaryKey();

        Route::get('products-by-category/{id}', 'ajaxGetProductsByCategory')
            ->name('products-by-category')
            ->wherePrimaryKey();

        Route::get('search-products', 'ajaxSearchProducts')
            ->name('search-products');
    });
});

Theme::routes();

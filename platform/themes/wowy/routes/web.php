<?php

/**
 * Rutas del tema Wowy - Stub de compatibilidad para inoqualabs.
 *
 * Las rutas AJAX originales usaban controladores y facades de Botble
 * que no existen en inoqualabs. Este stub evita errores fatales.
 */

use Illuminate\Support\Facades\Route;

// Rutas AJAX del tema Wowy (deshabilitadas hasta adaptación completa)
// Route::group(['prefix' => 'ajax', 'as' => 'public.ajax.'], function (): void {
//     Route::get('cart', fn () => response()->json(['count' => 0, 'html' => '']))->name('cart');
//     Route::get('quick-view/{id}', fn () => response()->json([]))->name('quick-view');
//     Route::get('products-by-collection/{id}', fn () => response()->json([]))->name('products-by-collection');
//     Route::get('products-by-category/{id}', fn () => response()->json([]))->name('products-by-category');
//     Route::get('search-products', fn () => response()->json([]))->name('search-products');
// });

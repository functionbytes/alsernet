<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Api\BlogApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Blog Module
|--------------------------------------------------------------------------
*/

Route::prefix('blog')->name('api.blog.')->group(function () {
    Route::get('/posts', [BlogApiController::class, 'index'])->name('posts.index');
    Route::get('/posts/{slug}', [BlogApiController::class, 'show'])->name('posts.show');
    Route::get('/categories', [BlogApiController::class, 'categories'])->name('categories.index');
    Route::get('/tags', [BlogApiController::class, 'tags'])->name('tags.index');
    Route::get('/search', [BlogApiController::class, 'search'])->name('search');
});

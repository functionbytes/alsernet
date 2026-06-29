<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogCategoryController;
use Modules\Blog\Http\Controllers\BlogCommentAdminController;
use Modules\Blog\Http\Controllers\BlogCommentController;
use Modules\Blog\Http\Controllers\BlogPostController;
use Modules\Blog\Http\Controllers\BlogPostTranslationController;
use Modules\Blog\Http\Controllers\BlogPublicController;
use Modules\Blog\Http\Controllers\BlogSettingsController;
use Modules\Blog\Http\Controllers\BlogTagController;
use Modules\Blog\Http\Controllers\BlogTranslationDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes - Blog Module
|--------------------------------------------------------------------------
*/

// ===== ADMIN POSTS =====
Route::middleware(['auth'])->group(function () {

    Route::prefix('panel/blog/posts')->name('blog.posts.')->group(function () {
        Route::get('/', [BlogPostController::class, 'index'])->name('index');
        Route::get('/create', [BlogPostController::class, 'create'])->name('create');
        Route::post('/', [BlogPostController::class, 'store'])->name('store');
        Route::post('/bulk-action', [BlogPostController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{post}/edit', [BlogPostController::class, 'edit'])->name('edit');
        Route::put('/{post}', [BlogPostController::class, 'update'])->name('update');
        Route::delete('/{post}', [BlogPostController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/publish', [BlogPostController::class, 'publish'])->name('publish');
        Route::post('/{post}/unpublish', [BlogPostController::class, 'unpublish'])->name('unpublish');
        Route::post('/{post}/translate', [BlogPostTranslationController::class, 'translate'])->name('translate');
        Route::post('/{post}/auto-translate', [BlogPostTranslationController::class, 'autoTranslate'])->name('auto-translate');
        Route::post('/{post}/translations/{locale}/reviewed', [BlogPostTranslationController::class, 'markReviewed'])->name('translations.reviewed');
        Route::delete('/{post}/translations/{locale}', [BlogPostTranslationController::class, 'destroyTranslation'])->name('translations.destroy');
        Route::post('/{post}/translate-fields', [BlogPostTranslationController::class, 'translateFields'])->name('translate-fields');
        Route::get('/{post}/versions', [BlogPostController::class, 'versions'])->name('versions');
        Route::post('/{post}/versions/{version}/restore', [BlogPostController::class, 'restoreVersion'])->name('versions.restore');
        Route::post('/{post}/duplicate', [BlogPostController::class, 'duplicate'])->name('duplicate');
        Route::get('/{post}/versions/{version}/diff', [BlogPostController::class, 'versionDiff'])->name('versions.diff');
        Route::get('/{post}/preview', [BlogPostController::class, 'preview'])->name('preview');
        Route::get('/{post}/translation-logs', [BlogPostTranslationController::class, 'logs'])->name('translation-logs');
    });

    // Translation management
    Route::prefix('panel/blog/translations')->name('blog.translations.')->group(function () {
        Route::get('/', [BlogTranslationDashboardController::class, 'index'])->name('dashboard');
        Route::get('/metrics', [BlogTranslationDashboardController::class, 'metrics'])->name('metrics');
        Route::get('/export', [BlogPostTranslationController::class, 'export'])->name('export');
        Route::post('/import', [BlogPostTranslationController::class, 'import'])->name('import');
    });

    Route::post('/panel/ajax/blog/posts/slug', [BlogPostController::class, 'ajaxSlug'])->name('blog.posts.ajax.slug');

    // ===== ADMIN CATEGORIES =====
    Route::prefix('panel/blog/categories')->name('blog.categories.')->group(function () {
        Route::get('/', [BlogCategoryController::class, 'index'])->name('index');
        Route::get('/create', [BlogCategoryController::class, 'create'])->name('create');
        Route::post('/', [BlogCategoryController::class, 'store'])->name('store');
        Route::post('/bulk-action', [BlogCategoryController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{category}/edit', [BlogCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [BlogCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [BlogCategoryController::class, 'destroy'])->name('destroy');
    });

    // ===== ADMIN TAGS =====
    Route::prefix('panel/blog/tags')->name('blog.tags.')->group(function () {
        Route::get('/', [BlogTagController::class, 'index'])->name('index');
        Route::get('/create', [BlogTagController::class, 'create'])->name('create');
        Route::post('/', [BlogTagController::class, 'store'])->name('store');
        Route::post('/bulk-action', [BlogTagController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{tag}/edit', [BlogTagController::class, 'edit'])->name('edit');
        Route::put('/{tag}', [BlogTagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [BlogTagController::class, 'destroy'])->name('destroy');
    });

    Route::get('/panel/ajax/blog/tags/all', [BlogTagController::class, 'ajaxAll'])->name('blog.tags.ajax.all');
    Route::post('/panel/ajax/blog/tags/slug', [BlogTagController::class, 'ajaxSlug'])->name('blog.tags.ajax.slug');
    Route::post('/panel/ajax/blog/categories/slug', [BlogCategoryController::class, 'ajaxSlug'])->name('blog.categories.ajax.slug');

    // ===== SETTINGS =====
    Route::prefix('panel/settings/blog')->name('settings.blog.')->group(function () {
        Route::get('/', [BlogSettingsController::class, 'index'])->name('index');
        Route::put('/', [BlogSettingsController::class, 'update'])->name('update');
    });

    // ===== ADMIN COMMENTS (moderation) =====
    Route::prefix('panel/blog/comments')->name('blog.comments.')->group(function () {
        Route::get('/', [BlogCommentAdminController::class, 'index'])->name('index');
        Route::post('/bulk-action', [BlogCommentAdminController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{comment}/approve', [BlogCommentAdminController::class, 'approve'])->name('approve');
        Route::post('/{comment}/spam', [BlogCommentAdminController::class, 'spam'])->name('spam');
        Route::delete('/{comment}', [BlogCommentAdminController::class, 'destroy'])->name('destroy');
    });
});

// ===== RSS FEED =====
Route::get('blog/rss', [BlogPublicController::class, 'rss'])->name('blog.rss');

// ===== PUBLIC ROUTES =====
Route::prefix('blog')->name('blog.public.')->group(function () {
    Route::get('/', [BlogPublicController::class, 'index'])->name('index');
    Route::get('/search', [BlogPublicController::class, 'search'])->name('search');
    Route::get('/category/{slug}', [BlogPublicController::class, 'category'])->name('category');
    Route::get('/tag/{slug}', [BlogPublicController::class, 'tag'])->name('tag');
    Route::get('/{slug}', [BlogPublicController::class, 'post'])->name('post');
    Route::post('/{slug}/comments', [BlogCommentController::class, 'store'])
        ->name('comments.store')
        ->middleware('throttle:5,1');
});

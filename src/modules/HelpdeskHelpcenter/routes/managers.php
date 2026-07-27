<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskHelpcenter\Http\Controllers\Managers\HelpCenterController;
use Modules\HelpdeskHelpcenter\Http\Controllers\Settings\ArticleTranslationsController;

Route::prefix('helpcenter')->group(function () {
    Route::get('/', [HelpCenterController::class, 'index'])->name('manager.helpcenter.index');

    // Categories
    Route::get('/categories', [HelpCenterController::class, 'index'])->name('manager.helpcenter.categories');
    Route::get('/categories/create', [HelpCenterController::class, 'create'])->name('manager.helpcenter.categories.create');
    Route::post('/categories/store', [HelpCenterController::class, 'store'])->name('manager.helpcenter.categories.store');
    Route::get('/categories/{id}', [HelpCenterController::class, 'showCategory'])->name('manager.helpcenter.categories.show');
    Route::get('/categories/edit/{id}', [HelpCenterController::class, 'edit'])->name('manager.helpcenter.categories.edit');
    Route::post('/categories/update', [HelpCenterController::class, 'update'])->name('manager.helpcenter.categories.update');
    Route::delete('/categories/{id}', [HelpCenterController::class, 'destroy'])->name('manager.helpcenter.categories.destroy');

    // Sections
    Route::get('/sections/create', [HelpCenterController::class, 'createSection'])->name('manager.helpcenter.sections.create');
    Route::post('/sections/store', [HelpCenterController::class, 'storeSection'])->name('manager.helpcenter.sections.store');
    Route::get('/sections/{id}', [HelpCenterController::class, 'showSection'])->name('manager.helpcenter.sections.show');
    Route::get('/sections/{id}/edit', [HelpCenterController::class, 'editSection'])->name('manager.helpcenter.sections.edit');
    Route::post('/sections/update', [HelpCenterController::class, 'updateSection'])->name('manager.helpcenter.sections.update');
    Route::delete('/sections/{id}', [HelpCenterController::class, 'destroySection'])->name('manager.helpcenter.sections.destroy');
    Route::get('/sections/{id}/articles/create', [HelpCenterController::class, 'createArticleInSection'])->name('manager.helpcenter.sections.articles.create');

    // Articles
    Route::get('/articles', [HelpCenterController::class, 'articlesIndex'])->name('manager.helpcenter.articles');
    Route::get('/articles/create', [HelpCenterController::class, 'createArticle'])->name('manager.helpcenter.articles.create');
    Route::post('/articles/store', [HelpCenterController::class, 'storeArticle'])->name('manager.helpcenter.articles.store');
    Route::get('/articles/edit/{id}', [HelpCenterController::class, 'editArticle'])->name('manager.helpcenter.articles.edit');
    Route::post('/articles/update', [HelpCenterController::class, 'updateArticle'])->name('manager.helpcenter.articles.update');
    Route::delete('/articles/{id}', [HelpCenterController::class, 'destroyArticle'])->name('manager.helpcenter.articles.destroy');

    // Article translations
    Route::prefix('/articles/{article}/translations')->name('manager.helpcenter.articles.translations.')->group(function () {
        Route::get('/', [ArticleTranslationsController::class, 'index'])->name('index');
        Route::get('/{locale}/edit', [ArticleTranslationsController::class, 'edit'])->name('edit');
        Route::post('/{locale}', [ArticleTranslationsController::class, 'update'])->name('update');
        Route::delete('/{locale}', [ArticleTranslationsController::class, 'destroy'])->name('destroy');
    });

    // Internal SPA JSON API (used by index.blade.php JS)
    Route::prefix('api')->group(function () {
        Route::get('/categories', [HelpCenterController::class, 'apiCategories'])->name('manager.helpcenter.api.categories');
        Route::post('/categories', [HelpCenterController::class, 'store'])->name('manager.helpcenter.api.categories.create');
        Route::post('/categories/reorder', [HelpCenterController::class, 'apiReorderCategories'])->name('manager.helpcenter.api.categories.reorder');
        Route::delete('/categories/{id}', [HelpCenterController::class, 'destroy'])->name('manager.helpcenter.api.categories.delete');

        Route::get('/categories/{id}/sections', [HelpCenterController::class, 'apiSections'])->name('manager.helpcenter.api.sections');

        Route::get('/sections/{id}/articles', [HelpCenterController::class, 'apiSectionArticles'])->name('manager.helpcenter.api.articles');
        Route::post('/articles', [HelpCenterController::class, 'storeArticle'])->name('manager.helpcenter.api.articles.create');
        Route::post('/sections/{id}/articles/reorder', [HelpCenterController::class, 'apiReorderArticles'])->name('manager.helpcenter.api.articles.reorder');
        Route::delete('/articles/{id}', [HelpCenterController::class, 'destroyArticle'])->name('manager.helpcenter.api.articles.delete');
    });
});

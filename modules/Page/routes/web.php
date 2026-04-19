<?php

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\Api\PageLockController;
use Modules\Page\Http\Controllers\PageAnalyticsController;
use Modules\Page\Http\Controllers\PageApprovalController;
use Modules\Page\Http\Controllers\PageCacheDashboardController;
use Modules\Page\Http\Controllers\PageCategoryController;
use Modules\Page\Http\Controllers\PageController;
use Modules\Page\Http\Controllers\PageImportExportController;
use Modules\Page\Http\Controllers\PagePerformanceController;
use Modules\Page\Http\Controllers\PageSettingsController;
use Modules\Page\Http\Controllers\PageTranslationController;
use Modules\Page\Http\Controllers\PageVersionController;
use Modules\Page\Http\Controllers\PageWebhookController;
use Modules\Page\Http\Controllers\PreviewController;
use Modules\Page\Http\Controllers\PublicController;
use Modules\Page\Http\Controllers\RobotsController;
use Modules\Page\Http\Controllers\SitemapController;
use Modules\Page\Http\Controllers\VeUserPreferencesController;
use Modules\Page\Http\Controllers\VisualEditorController;

/*
|--------------------------------------------------------------------------
| Admin Routes (authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('panel')->group(function () {

    Route::prefix('pages/webhooks')->name('pages.webhooks.')->group(function () {
        Route::post('/bulk-action', [PageWebhookController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/', [PageWebhookController::class, 'index'])->name('index');
        Route::get('/create', [PageWebhookController::class, 'create'])->name('create');
        Route::post('/', [PageWebhookController::class, 'store'])->name('store');
        Route::get('/{webhook}/edit', [PageWebhookController::class, 'edit'])->name('edit');
        Route::put('/{webhook}', [PageWebhookController::class, 'update'])->name('update');
        Route::delete('/{webhook}', [PageWebhookController::class, 'destroy'])->name('destroy');
        Route::patch('/{webhook}/toggle', [PageWebhookController::class, 'toggle'])->name('toggle');
        Route::post('/{webhook}/test', [PageWebhookController::class, 'test'])->middleware('throttle:10,1')->name('test');
    });

    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/search', [PageController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('pages.search');
    Route::post('pages/approvals/bulk-action', [PageApprovalController::class, 'bulkAction'])->name('pages.approvals.bulk-action');
    Route::get('pages/approvals', [PageApprovalController::class, 'index'])->name('pages.approvals.index');
    Route::post('pages/approvals/{approval}/approve', [PageApprovalController::class, 'approve'])->name('pages.approvals.approve');
    Route::post('pages/approvals/{approval}/reject', [PageApprovalController::class, 'reject'])->name('pages.approvals.reject');
    Route::get('pages/export', [PageImportExportController::class, 'exportForm'])->name('pages.export');
    Route::get('pages/export/download', [PageImportExportController::class, 'export'])->name('pages.export.download');
    Route::get('pages/import', [PageImportExportController::class, 'importForm'])->name('pages.import');
    Route::post('pages/import', [PageImportExportController::class, 'import'])->name('pages.import.process');
    Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::get('pages/cache/dashboard', [PageCacheDashboardController::class, 'index'])->name('pages.cache.dashboard');
    Route::get('pages/cache/stats', [PageCacheDashboardController::class, 'stats'])->name('pages.cache.stats');
    Route::get('pages/cache/audits', [PageCacheDashboardController::class, 'audits'])->name('pages.cache.audits');
    Route::post('pages/cache/warm', [PageCacheDashboardController::class, 'warm'])->name('pages.cache.warm');
    Route::post('pages/cache/clear', [PageCacheDashboardController::class, 'clear'])->name('pages.cache.clear');
    Route::get('settings/pages', [PageSettingsController::class, 'edit'])->name('settings.pages');
    Route::put('settings/pages', [PageSettingsController::class, 'update'])->name('settings.pages.update');
    Route::post('ajax/pages/slug', [PageController::class, 'ajaxSlug'])->name('pages.ajax.slug');
    Route::post('pages', [PageController::class, 'store'])->name('pages.store');
    Route::get('pages/analytics/overview', [PageAnalyticsController::class, 'overview'])->name('pages.analytics.overview');

    Route::prefix('pages/categories')->name('pages.categories.')->group(function () {
        Route::post('/bulk-action', [PageCategoryController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/', [PageCategoryController::class, 'index'])->name('index');
        Route::get('/create', [PageCategoryController::class, 'create'])->name('create');
        Route::post('/', [PageCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [PageCategoryController::class, 'edit'])->name('edit');
        Route::patch('/{category}', [PageCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [PageCategoryController::class, 'destroy'])->name('destroy');
        Route::patch('/{category}/toggle', [PageCategoryController::class, 'toggle'])->name('toggle');
        Route::post('/reorder', [PageCategoryController::class, 'reorder'])->name('reorder');
    });

    Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');
    Route::get('pages/{page}/visual-editor', [VisualEditorController::class, 'index'])->name('pages.visual-editor');
    Route::get('pages/{page}/visual-preview', [VisualEditorController::class, 'preview'])->name('pages.visual-preview');
    Route::post('pages/{page}/visual-preview', [VisualEditorController::class, 'preview'])->name('pages.visual-preview.post');
    Route::post('pages/{page}/visual-save', [VisualEditorController::class, 'save'])->name('pages.visual-save');
    Route::get('pages/{page}/draft', [VisualEditorController::class, 'getDraft'])->name('pages.draft');
    Route::patch('pages/{page}/auto-save', [VisualEditorController::class, 'autoSave'])->name('pages.auto-save');
    Route::get('pages/{page}/locale-content', [VisualEditorController::class, 'getLocaleContent'])->name('pages.locale-content');
    Route::post('pages/{page}/expand-shortcode', [VisualEditorController::class, 'expandShortcode'])->name('pages.expand-shortcode');
    Route::get('pages/{page}/editor-versions', [VisualEditorController::class, 'getEditorVersions'])->name('pages.editor-versions');
    Route::get('pages/{page}/editor-versions/{version}', [VisualEditorController::class, 'getEditorVersion'])->name('pages.editor-version');

    // Page lock (session-authenticated aliases of the Sanctum API routes — the
    // visual editor runs on web guard and cannot reach /api/v1/* without
    // SANCTUM_STATEFUL_DOMAINS including the panel host).
    Route::get('pages/{page}/lock', [PageLockController::class, 'check'])->name('pages.lock.check');
    Route::post('pages/{page}/lock', [PageLockController::class, 'acquire'])->name('pages.lock.acquire');
    Route::patch('pages/{page}/lock', [PageLockController::class, 'renew'])->name('pages.lock.renew');
    Route::delete('pages/{page}/lock', [PageLockController::class, 'release'])->name('pages.lock.release');

    // Visual editor user preferences (shortcode favorites, panel states, etc.).
    Route::get('pages/ve/preferences/{key}', [VeUserPreferencesController::class, 'show'])->name('pages.ve.preferences.show');
    Route::post('pages/ve/preferences/{key}', [VeUserPreferencesController::class, 'store'])->name('pages.ve.preferences.store');

    Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

    Route::post('pages/{page}/approval/request', [PageApprovalController::class, 'request'])->name('pages.approval.request');
    Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
    Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
    Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
    Route::post('pages/{id}/restore', [PageController::class, 'restore'])->name('pages.restore');
    Route::delete('pages/{id}/force-delete', [PageController::class, 'forceDelete'])->name('pages.force-delete');
    Route::post('pages/bulk-action', [PageController::class, 'bulkAction'])->name('pages.bulk-action');

    Route::prefix('pages/{page}/versions')->name('pages.versions.')->group(function () {
        Route::get('/', [PageVersionController::class, 'index'])->name('index');
        Route::post('/create', [PageVersionController::class, 'create'])->name('create');
        Route::get('/compare', [PageVersionController::class, 'compare'])->name('compare');
        Route::get('/{version}', [PageVersionController::class, 'show'])->name('show');
        Route::post('/{version}/restore', [PageVersionController::class, 'restore'])->name('restore');
        Route::delete('/{version}', [PageVersionController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [PageVersionController::class, 'bulkAction'])->name('bulk-action');
    });

    Route::prefix('pages/{page}/preview')->name('pages.preview.')->group(function () {
        Route::get('/', [PreviewController::class, 'index'])->name('index');
        Route::post('/generate', [PreviewController::class, 'generate'])->name('generate');
        Route::post('/revoke', [PreviewController::class, 'revoke'])->name('revoke');
    });

    Route::get('pages/performance/top', [PagePerformanceController::class, 'top'])->name('pages.performance.top');

    Route::prefix('pages/{page}/performance')->name('pages.performance.')->group(function () {
        Route::get('/', [PagePerformanceController::class, 'show'])->name('show');
        Route::post('/scan', [PagePerformanceController::class, 'scan'])->name('scan');
    });

    Route::post('pages/{page}/translate', [PageTranslationController::class, 'translate'])->name('pages.translate');
    Route::post('pages/{page}/auto-translate', [PageTranslationController::class, 'autoTranslate'])->name('pages.auto-translate');

    Route::get('pages/{page}/analytics', [PageAnalyticsController::class, 'show'])->name('pages.analytics.show');
    Route::get('pages/{page}/analytics/export', [PageAnalyticsController::class, 'export'])->name('pages.analytics.export');
    Route::get('pages/{page}/analytics/view', [PageController::class, 'analytics'])->name('pages.analytics.view');
});

Route::get('/preview/{slug}/{token}', [PreviewController::class, 'show'])->name('page.preview')->where('slug', '[\p{L}0-9\-\/]+')->where('token', '[a-zA-Z0-9]{64}');

// robots.txt dinámico
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');

// Sitemap XML
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Homepage
Route::get('/', [PublicController::class, 'showHomepage'])->name('page.home');

// Catchall route for pages - must be last
Route::get('/{path}', [PublicController::class, 'show'])->name('page.show')
    ->where('path', '^(?!panel|dashboard|login|logout|register|password|settings|manager|setting|api|up|broadcasting|pages|preview|pqrsf|attentions|media|reviews|templates|reply-templates|blog|helpdesk|forms|mailrelay)([\p{L}0-9\-\/]+)$');

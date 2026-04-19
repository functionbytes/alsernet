<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\Http\Controllers\IndexNowController;
use Modules\Seo\Http\Controllers\IndexNowKeyController;
use Modules\Seo\Http\Controllers\LlmsTxtController;
use Modules\Seo\Http\Controllers\RobotsTxtController;
use Modules\Seo\Http\Controllers\SchemaOrgController;
use Modules\Seo\Http\Controllers\Seo404Controller;
use Modules\Seo\Http\Controllers\SeoAuditController;
use Modules\Seo\Http\Controllers\SeoAuditHistoryController;
use Modules\Seo\Http\Controllers\SeoDashboardController;
use Modules\Seo\Http\Controllers\SeoMetaWebController;
use Modules\Seo\Http\Controllers\SeoOrphanController;
use Modules\Seo\Http\Controllers\SeoPageUrlsController;
use Modules\Seo\Http\Controllers\SeoRedirectController;
use Modules\Seo\Http\Controllers\SeoReportController;
use Modules\Seo\Http\Controllers\SeoStaticUrlController;
use Modules\Seo\Http\Controllers\SeoTemplateController;
use Modules\Seo\Http\Controllers\SeoWebVitalsController;
use Modules\Seo\Http\Controllers\SitemapAdminController;
use Modules\Seo\Http\Controllers\SitemapController;
use Modules\Seo\Http\Controllers\WebVitalsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Seo module.
|
*/

Route::prefix('panel/setting')->middleware(['web', 'auth'])->name('setting.')->group(function () {
    Route::prefix('seo')->name('seo.')->group(function () {
        // Dashboard
        Route::get('dashboard', [SeoDashboardController::class, 'index'])->name('dashboard');
        Route::post('dashboard/clear-cache', [SeoDashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
        Route::get('analytics', [SeoDashboardController::class, 'analytics'])->name('analytics.index');
        Route::get('verification', [SeoDashboardController::class, 'verification'])->name('verification.index');
        Route::put('verification', [SeoDashboardController::class, 'verificationUpdate'])->name('verification.update');

        Route::resource('metas', SeoMetaWebController::class)->except(['create', 'store']);
        Route::get('metas/keyword-suggestions', [SeoMetaWebController::class, 'keywordSuggestions'])->name('metas.keyword-suggestions');
        Route::get('metas/{meta}/checklist', [SeoMetaWebController::class, 'checklist'])->name('metas.checklist');
        Route::post('metas/{meta}/generate-og-image', [SeoMetaWebController::class, 'generateOgImage'])->name('metas.generate-og-image');
        Route::patch('metas/{meta}/inline-update', [SeoMetaWebController::class, 'inlineUpdate'])->name('metas.inline-update');
        Route::post('metas/{meta}/generate-canonical', [SeoMetaWebController::class, 'generateCanonical'])->name('metas.generate-canonical');
        Route::post('metas/badge-data', [SeoMetaWebController::class, 'badgeData'])->name('metas.badge-data');
        Route::delete('metas-bulk-delete', [SeoMetaWebController::class, 'bulkDelete'])->name('metas.bulk-delete');
        Route::post('metas-bulk-robots', [SeoMetaWebController::class, 'bulkUpdateRobots'])->name('metas.bulk-robots');
        Route::get('metas-export', [SeoMetaWebController::class, 'export'])->name('metas.export');
        Route::get('metas-import', [SeoMetaWebController::class, 'showImport'])->name('metas.import');
        Route::post('metas-import', [SeoMetaWebController::class, 'import'])->middleware('throttle:10,60')->name('metas.import.store');
        Route::get('metas-hreflang', [SeoMetaWebController::class, 'hreflangIndex'])->name('metas.hreflang');
        Route::post('metas/{meta}/translate', [SeoMetaWebController::class, 'translateMeta'])->name('metas.translate');
        Route::post('metas/{meta}/create-locale', [SeoMetaWebController::class, 'createLocale'])->name('metas.create-locale');
        Route::get('metas/{meta}/score-history', [SeoMetaWebController::class, 'scoreHistory'])->name('metas.score-history');
        Route::post('metas-bulk-generate-canonicals', [SeoMetaWebController::class, 'bulkGenerateCanonicals'])->name('metas.bulk-generate-canonicals');
        Route::get('metas-export-json', [SeoMetaWebController::class, 'exportJson'])->name('metas.export-json');
        Route::get('metas-import-json', [SeoMetaWebController::class, 'showImportJson'])->name('metas.import-json');
        Route::post('metas-import-json', [SeoMetaWebController::class, 'importJson'])->middleware('throttle:5,60')->name('metas.import-json.store');

        Route::resource('redirects', SeoRedirectController::class);
        Route::patch('redirects/{redirect}/toggle-active', [SeoRedirectController::class, 'toggleActive'])->name('redirects.toggle-active');
        Route::delete('redirects-bulk-delete', [SeoRedirectController::class, 'bulkDelete'])->name('redirects.bulk-delete');
        Route::post('redirects-clear-cache', [SeoRedirectController::class, 'clearCache'])->name('redirects.clear-cache');
        Route::get('redirects-detect-chains', [SeoRedirectController::class, 'detectChains'])->name('redirects.detect-chains');
        Route::get('redirects-export', [SeoRedirectController::class, 'export'])->name('redirects.export');
        Route::get('redirects-import', [SeoRedirectController::class, 'showImport'])->name('redirects.import');
        Route::post('redirects-import', [SeoRedirectController::class, 'import'])->middleware('throttle:10,60')->name('redirects.import.store');
        Route::get('redirects-htaccess-import', [SeoRedirectController::class, 'showHtaccessImport'])->name('redirects.htaccess-import');
        Route::post('redirects-htaccess-import', [SeoRedirectController::class, 'importHtaccess'])->middleware('throttle:10,60')->name('redirects.htaccess-import.store');

        Route::get('robots', [RobotsTxtController::class, 'edit'])->name('robots.edit');
        Route::post('robots', [RobotsTxtController::class, 'update'])->name('robots.update');
        Route::post('robots/reset', [RobotsTxtController::class, 'reset'])->name('robots.reset');
        Route::post('robots/test-url', [RobotsTxtController::class, 'testUrl'])->name('robots.test-url');

        Route::get('llms', [LlmsTxtController::class, 'edit'])->name('llms.edit');
        Route::post('llms', [LlmsTxtController::class, 'update'])->name('llms.update');
        Route::post('llms/reset', [LlmsTxtController::class, 'reset'])->name('llms.reset');

        Route::get('indexnow', [IndexNowController::class, 'index'])->name('indexnow.index');
        Route::post('indexnow/submit', [IndexNowController::class, 'submit'])->middleware('throttle:10,60')->name('indexnow.submit');

        // Core Web Vitals dashboard
        Route::get('web-vitals', [WebVitalsController::class, 'index'])->name('web-vitals.index');
        Route::get('web-vitals/path/{path}', [WebVitalsController::class, 'show'])
            ->where('path', '.*')
            ->name('web-vitals.show');

        // Schema.org per-page editor
        Route::get('metas/{meta}/schema-org', [SchemaOrgController::class, 'edit'])->name('schema-org.edit');
        Route::put('metas/{meta}/schema-org', [SchemaOrgController::class, 'update'])->name('schema-org.update');
        Route::post('metas/{meta}/schema-org/validate', [SchemaOrgController::class, 'validateJson'])->name('schema-org.validate');
        Route::get('schema-org/template/{type}', [SchemaOrgController::class, 'template'])->name('schema-org.template');
        Route::post('redirects/{redirect}/test', [SeoRedirectController::class, 'test'])->name('redirects.test');
        Route::get('redirects/{redirect}/analytics', [SeoRedirectController::class, 'analytics'])->name('redirects.analytics');

        Route::get('sitemap', [SitemapAdminController::class, 'index'])->name('sitemap.index');
        Route::post('sitemap/generate', [SitemapAdminController::class, 'generate'])->name('sitemap.generate');
        Route::post('sitemap/clear-cache', [SitemapAdminController::class, 'clearCache'])->name('sitemap.clear-cache');
        Route::get('sitemap/calculate-priorities', [SitemapAdminController::class, 'calculatePriorities'])->name('sitemap.calculate-priorities');

        // SEO Audit
        Route::get('audit', [SeoAuditController::class, 'index'])->name('audit.index');
        Route::post('audit/url', [SeoAuditController::class, 'auditUrl'])->middleware('throttle:10,1')->name('audit.url');
        Route::get('audit/all', [SeoAuditController::class, 'auditAll'])->name('audit.all');
        Route::get('audit/check-canonicals', [SeoAuditController::class, 'checkCanonicals'])->name('audit.check-canonicals');
        Route::post('audit/bulk-start', [SeoAuditController::class, 'startBulkAudit'])->middleware('throttle:3,60')->name('audit.bulk-start');
        Route::get('audit/bulk-progress', [SeoAuditController::class, 'bulkAuditProgress'])->name('audit.bulk-progress');
        Route::post('audit/broken-links-start', [SeoAuditController::class, 'startBrokenLinksCheck'])->middleware('throttle:3,60')->name('audit.broken-links-start');
        Route::get('audit/broken-links-progress', [SeoAuditController::class, 'brokenLinksProgress'])->name('audit.broken-links-progress');
        Route::get('audit/internal-links', [SeoAuditController::class, 'analyzeInternalLinks'])->name('audit.internal-links');
        Route::post('audit/core-web-vitals', [SeoAuditController::class, 'coreWebVitals'])->middleware('throttle:20,1')->name('audit.core-web-vitals');

        // URLs del Sitio (módulo Page)
        Route::get('page-urls', [SeoPageUrlsController::class, 'index'])->name('page-urls.index');
        Route::post('page-urls/quick-redirect', [SeoPageUrlsController::class, 'quickRedirect'])->name('page-urls.quick-redirect');
        Route::post('page-urls/bulk-action', [SeoPageUrlsController::class, 'bulkAction'])->name('page-urls.bulk-action');

        // URLs estáticas del sitemap
        Route::resource('static-urls', SeoStaticUrlController::class)->except(['show'])->parameters(['static-urls' => 'seoStaticUrl']);
        Route::post('static-urls/bulk-action', [SeoStaticUrlController::class, 'bulkAction'])->name('static-urls.bulk-action');
        Route::patch('static-urls/{seoStaticUrl}/toggle-active', [SeoStaticUrlController::class, 'toggleActive'])->name('static-urls.toggle-active');

        // Contenido sin SEO (huérfanos)
        Route::get('orphans', [SeoOrphanController::class, 'index'])->name('orphans.index');
        Route::post('orphans/generate', [SeoOrphanController::class, 'generate'])->name('orphans.generate');
        Route::post('orphans/bulk-generate', [SeoOrphanController::class, 'bulkGenerate'])->name('orphans.bulk-generate');

        // Verificación de URLs del sitemap
        Route::get('sitemap/verify-urls', [SitemapAdminController::class, 'verifyUrls'])->name('sitemap.verify-urls');

        // Historial de auditorías
        Route::get('audit/history', [SeoAuditHistoryController::class, 'index'])->name('audit.history');
        Route::post('audit/history/bulk-action', [SeoAuditHistoryController::class, 'bulkAction'])->name('audit.history.bulk-action');
        Route::post('audit/history/clear', [SeoAuditHistoryController::class, 'clear'])->name('audit.history.clear');
        Route::delete('audit/history/{log}', [SeoAuditHistoryController::class, 'destroy'])->name('audit.history.destroy');
        Route::get('audit/history/{meta}', [SeoAuditHistoryController::class, 'forMeta'])->name('audit.history.meta');
        Route::get('audit/history/{meta}/json', [SeoAuditHistoryController::class, 'forMetaJson'])->name('audit.history.meta.json');

        // Errores 404
        Route::get('404-logs', [Seo404Controller::class, 'index'])->name('404-logs.index');
        Route::get('404-logs/suggest-redirects', [Seo404Controller::class, 'suggestRedirects'])->name('404-logs.suggest-redirects');
        Route::post('404-logs/bulk-create-redirects', [Seo404Controller::class, 'bulkCreateRedirects'])->name('404-logs.bulk-create-redirects');
        Route::post('404-logs/bulk-action', [Seo404Controller::class, 'bulkAction'])->name('404-logs.bulk-action');
        Route::post('404-logs/{log}/create-redirect', [Seo404Controller::class, 'createRedirect'])->name('404-logs.create-redirect');
        Route::patch('404-logs/{log}/mark-resolved', [Seo404Controller::class, 'markResolved'])->name('404-logs.mark-resolved');
        Route::post('404-logs/clear', [Seo404Controller::class, 'clear'])->name('404-logs.clear');
        Route::delete('404-logs/{log}', [Seo404Controller::class, 'destroy'])->name('404-logs.destroy');

        // Plantillas SEO
        Route::post('templates/bulk-action', [SeoTemplateController::class, 'bulkAction'])->name('templates.bulk-action');
        Route::resource('templates', SeoTemplateController::class)->except(['show']);
        Route::patch('templates/{template}/toggle-active', [SeoTemplateController::class, 'toggleActive'])->name('templates.toggle-active');
        Route::get('templates/{template}/preview', [SeoTemplateController::class, 'preview'])->name('templates.preview');
        Route::post('templates/{template}/bulk-apply', [SeoTemplateController::class, 'bulkApply'])->name('templates.bulk-apply');

        // Reporte SEO
        Route::get('report', [SeoReportController::class, 'index'])->name('report.index');
        Route::get('report/export', [SeoReportController::class, 'export'])->name('report.export');
        Route::get('report/export-pdf', [SeoReportController::class, 'exportPdf'])->name('report.export-pdf');

        // Search Console CSV Import
        Route::get('search-console/import', [SeoDashboardController::class, 'showSearchConsoleImport'])->name('search-console.import');
        Route::post('search-console/import', [SeoDashboardController::class, 'importSearchConsole'])->middleware('throttle:5,60')->name('search-console.import.store');
    });
});

// Public - Robots.txt Service
Route::get('/robots.txt', [RobotsTxtController::class, 'serve'])->middleware('web')->name('robots-txt.serve');

// Public - llms.txt (AI crawler directives)
Route::get('/llms.txt', [LlmsTxtController::class, 'serve'])->middleware('web')->name('llms-txt.serve');

// Public - IndexNow key file (permite a los motores verificar la propiedad del dominio)
Route::get('/{indexnowKey}.txt', IndexNowKeyController::class)
    ->where('indexnowKey', '[A-Za-z0-9]{8,128}')
    ->middleware('web')
    ->name('seo.indexnow.key');

// Public - Beacon ingest de Core Web Vitals (LCP / INP / CLS / FCP / TTFB)
Route::post('/api/seo/web-vitals', [SeoWebVitalsController::class, 'store'])
    ->middleware(['web', 'throttle:120,1'])
    ->name('seo.web-vitals.store');

// Public Sitemap Routes (sitemap.xml is handled by the Page module as 'sitemap')
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-posts.xml', [SitemapController::class, 'posts'])->name('sitemap.posts');
Route::get('/sitemap-index.xml', [SitemapController::class, 'sitemapIndex'])->name('sitemap.sitemap-index');
Route::get('/sitemap-images.xml', [SitemapController::class, 'images'])->name('sitemap.images');
Route::get('/sitemap-video.xml', [SitemapController::class, 'videos'])->name('sitemap.videos');
Route::get('/sitemap-news.xml', [SitemapController::class, 'news'])->name('sitemap.news');

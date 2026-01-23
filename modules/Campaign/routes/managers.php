<?php

use Illuminate\Support\Facades\Route;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations\AutomationsController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations\AutoTrigger;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Layouts\LayoutController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\MaillistController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\SegmentController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\SubscriberController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates\TemplatesController;
use Modules\Campaign\Http\Controllers\Managers\CampaignsController;

/*
|--------------------------------------------------------------------------
| Campaign Module Routes - Manager Area
|--------------------------------------------------------------------------
|
| All routes for the Campaign module management interface, following the
| modular architecture pattern. Routes are protected by 'web', 'auth', and
| 'verified' middleware, and prefixed with 'manager.'.
|
| This file is loaded via the Campaign Module's RouteServiceProvider.
|
| Feature Groups & Route Breakdown:
|
| 1. SUBSCRIBERS (25+ routes) - SubscriberController
|    - Individual subscriber CRUD
|    - Subscriber list management
|    - Import operations
|    - Conditions management
|
| 2. TEMPLATES (30+ routes) - TemplatesController
|    - Template CRUD operations
|    - Builder operations
|    - Template metadata and categories
|    - RSS parsing and listings
|
| 3. CAMPAIGNS (60+ routes) - CampaignsController
|    - Campaign CRUD and state management
|    - Campaign setup & configuration
|    - Template management within campaigns
|    - Preview and testing
|    - Webhooks management
|    - Tracking logs (opens, clicks, bounces, feedback)
|    - Analytics and charts
|    - Copy and export operations
|
| 4. SEGMENTS (15+ routes) - SegmentController
|    - Segment CRUD (nested under lists)
|    - Subscriber listing and filtering
|    - Condition management
|
| 5. MAILLISTS (40+ routes) - MaillistController
|    - List CRUD operations
|    - Email verification management
|    - Embedded forms
|    - Analytics and growth charts
|    - Subscriber cloning
|
| 6. AUTOMATIONS (35+ routes) - AutomationsController
|    - Automation setup and execution
|    - Email preheader management
|    - Trigger management
|    - Subscribers management
|    - Webhooks for automation emails
|    - Conditions and wait times
|    - Cart management
|
| 7. LAYOUTS (9 routes) - LayoutController
|    - Layout CRUD operations
|    - Listing and sorting
|
| Total Routes: 171+
|
*/

Route::group(
    [
        'middleware' => ['web', 'auth', 'verified', 'module:Campaign'],
        'prefix' => 'manager',
        'name' => 'manager.',
    ],
    function (): void {

        // ===================================================================
        // SUBSCRIBERS ROUTES - Individual subscriber management (25+ routes)
        // ===================================================================

        Route::group(['prefix' => 'subscribers', 'name' => 'subscribers.'], function (): void {

            // Individual subscriber CRUD operations
            Route::get('/', [SubscriberController::class, 'index'])->name('index');
            Route::get('/create', [SubscriberController::class, 'create'])->name('create');
            Route::post('/update', [SubscriberController::class, 'update'])->name('update');
            Route::get('/edit/{uid}', [SubscriberController::class, 'edit'])->name('edit');
            Route::get('/view/{uid}', [SubscriberController::class, 'view'])->name('view');
            Route::get('/destroy/{uid}', [SubscriberController::class, 'destroy'])->name('destroy');
            Route::get('/logs/{slack}', [SubscriberController::class, 'logs'])->name('logs');

            // Subscriber imports management
            Route::get('/imports/create', [SubscriberController::class, 'createImport'])->name('imports.create');
            Route::get('/imports/{import_uid}', [SubscriberController::class, 'createImports'])->name('imports');
            Route::post('/imports/{import_uid}/dispatch', [SubscriberController::class, 'dispatchImportListsJobs'])->name('imports.dispatch');
            Route::get('/imports/{job_uid}/progress', [SubscriberController::class, 'importListsProgress'])->name('imports.progress');
            Route::get('/imports/{job_uid}/log/download', [SubscriberController::class, 'downloadImportListsLog'])->name('imports.log.download');
            Route::post('/imports/{job_uid}/cancel', [SubscriberController::class, 'cancelImportLists'])->name('imports.cancel');

            // Subscriber lists CRUD operations
            Route::get('/lists', [SubscriberController::class, 'lists'])->name('lists');
            Route::get('/lists/create', [SubscriberController::class, 'listsCreate'])->name('lists.create');
            Route::post('/lists/store', [SubscriberController::class, 'listsStore'])->name('lists.store');
            Route::get('/lists/{uid}', [SubscriberController::class, 'listsView'])->name('lists.view');
            Route::get('/lists/{uid}/edit', [SubscriberController::class, 'listsEdit'])->name('lists.edit');
            Route::patch('/lists/{uid}/update', [SubscriberController::class, 'listsUpdate'])->name('lists.update');
            Route::delete('/lists/{uid}', [SubscriberController::class, 'listsDestroy'])->name('lists.destroy');
            Route::get('/lists/{uid}/details', [SubscriberController::class, 'listsDetails'])->name('lists.details');
            Route::get('/lists/{uid}/categories', [SubscriberController::class, 'listsCategories'])->name('lists.categories');
            Route::post('/lists/{uid}/categories/update', [SubscriberController::class, 'listsCategoriesUpdate'])->name('lists.categories.update');
            Route::get('/lists/{uid}/components', [SubscriberController::class, 'listsIncludes'])->name('lists.components');
            Route::post('/lists/{uid}/components/update', [SubscriberController::class, 'listsIncludesUpdate'])->name('lists.components.update');

            // Subscriber list reports
            Route::get('/lists/reports', [SubscriberController::class, 'listsReports'])->name('lists.reports');
            Route::post('/lists/reports/generate', [SubscriberController::class, 'listsReportsGenerate'])->name('lists.reports.generate');

            // Subscriber conditions CRUD operations
            Route::get('/conditions', [SubscriberController::class, 'conditionsIndex'])->name('conditions.index');
            Route::get('/conditions/create', [SubscriberController::class, 'conditionsCreate'])->name('conditions.create');
            Route::post('/conditions/store', [SubscriberController::class, 'conditionsStore'])->name('conditions.store');
            Route::get('/conditions/{uid}/edit', [SubscriberController::class, 'conditionsEdit'])->name('conditions.edit');
            Route::patch('/conditions/{uid}/update', [SubscriberController::class, 'conditionsUpdate'])->name('conditions.update');
            Route::get('/conditions/{uid}', [SubscriberController::class, 'conditionsView'])->name('conditions.view');
            Route::delete('/conditions/{uid}', [SubscriberController::class, 'conditionsDestroy'])->name('conditions.destroy');

        });

        // ===================================================================
        // TEMPLATES ROUTES - Email template management (30+ routes)
        // ===================================================================

        Route::group(['prefix' => 'templates', 'name' => 'templates.'], function (): void {

            // Template CRUD operations
            Route::get('/', [TemplatesController::class, 'index'])->name('index');
            Route::get('/create', [TemplatesController::class, 'create'])->name('create');
            Route::post('/store', [TemplatesController::class, 'store'])->name('store');
            Route::get('/edit/{uid}', [TemplatesController::class, 'edit'])->name('edit');
            Route::patch('/update/{uid}', [TemplatesController::class, 'update'])->name('update');
            Route::get('/view/{uid}', [TemplatesController::class, 'view'])->name('view');
            Route::delete('/{uid}', [TemplatesController::class, 'destroy'])->name('destroy');

            // Template management
            Route::post('/upload', [TemplatesController::class, 'uploadTemplate'])->name('upload');
            Route::get('/preview/{uid}', [TemplatesController::class, 'preview'])->name('preview');
            Route::post('/copy/{uid}', [TemplatesController::class, 'copy'])->name('copy');
            Route::post('/export/{uid}', [TemplatesController::class, 'export'])->name('export');

            // Template builder operations
            Route::match(['get', 'post'], '/builder/create', [TemplatesController::class, 'builderCreate'])->name('builder.create');
            Route::match(['get', 'post'], '/{uid}/builder/edit', [TemplatesController::class, 'builderEdit'])->name('builder.edit');
            Route::get('/builder/templates/{category_uid?}', [TemplatesController::class, 'builderTemplates'])->name('builder.templates');
            Route::post('/{uid}/builder/assets', [TemplatesController::class, 'uploadTemplateAssets'])->name('builder.assets');
            Route::get('/{uid}/builder/content', [TemplatesController::class, 'builderEditContent'])->name('builder.content');
            Route::post('/{uid}/builder/change-template/{change_uid}', [TemplatesController::class, 'builderChangeTemplate'])->name('builder.change');

            // Template metadata management
            Route::match(['get', 'post'], '/{uid}/name', [TemplatesController::class, 'changeName'])->name('name');
            Route::match(['get', 'post'], '/{uid}/categories', [TemplatesController::class, 'categories'])->name('categories');
            Route::match(['get', 'post'], '/{uid}/thumb', [TemplatesController::class, 'updateThumb'])->name('thumb');
            Route::match(['get', 'post'], '/{uid}/thumb-url', [TemplatesController::class, 'updateThumbUrl'])->name('thumb.url');

            // Template RSS and listings
            Route::get('/rss/parse', [TemplatesController::class, 'parseRss'])->name('rss.parse');
            Route::get('/listing/{page?}', [TemplatesController::class, 'listing'])->name('listing');
            Route::get('/choosing/{campaign_uid}/{page?}', [TemplatesController::class, 'choosing'])->name('choosing');

            // AI Chat integration
            Route::get('/chat', [TemplatesController::class, 'chat'])->name('chat');

        });

        // ===================================================================
        // CAMPAIGNS ROUTES - Email campaign management (60+ routes)
        // ===================================================================

        Route::group(['prefix' => 'campaigns', 'name' => 'campaigns.'], function (): void {

            // Campaign CRUD operations
            Route::get('/', [CampaignsController::class, 'index'])->name('index');
            Route::get('/create', [CampaignsController::class, 'create'])->name('create');
            Route::post('/store', [CampaignsController::class, 'store'])->name('store');
            Route::get('/{uid}', [CampaignsController::class, 'view'])->name('view');
            Route::get('/{uid}/edit', [CampaignsController::class, 'edit'])->name('edit');
            Route::patch('/{uid}', [CampaignsController::class, 'update'])->name('update');
            Route::delete('/{uid}', [CampaignsController::class, 'destroy'])->name('destroy');

            // Campaign state management
            Route::post('/pause', [CampaignsController::class, 'pause'])->name('pause');
            Route::post('/restart', [CampaignsController::class, 'restart'])->name('restart');
            Route::get('/{uid}/run', [CampaignsController::class, 'run'])->name('run');
            Route::match(['get', 'post'], '/{uid}/resend', [CampaignsController::class, 'resend'])->name('resend');

            // Campaign setup and configuration
            Route::match(['get', 'post'], '/{uid}/setup', [CampaignsController::class, 'setup'])->name('setup');
            Route::match(['get', 'post'], '/{uid}/template', [CampaignsController::class, 'template'])->name('template');
            Route::match(['get', 'post'], '/{uid}/recipients', [CampaignsController::class, 'recipients'])->name('recipients');
            Route::match(['get', 'post'], '/{uid}/schedule', [CampaignsController::class, 'schedule'])->name('schedule');
            Route::match(['get', 'post'], '/{uid}/confirm', [CampaignsController::class, 'confirm'])->name('confirm');
            Route::get('/{uid}/list-segment-form', [CampaignsController::class, 'listSegmentForm'])->name('list.segment.form');
            Route::get('/select-type', [CampaignsController::class, 'selecttype'])->name('selecttype');

            // Campaign template management
            Route::get('/{uid}/template/select', [CampaignsController::class, 'templateSelect'])->name('template.select');
            Route::get('/{uid}/template/choose/{template_uid}', [CampaignsController::class, 'templateChoose'])->name('template.choose');
            Route::get('/{uid}/template/create', [CampaignsController::class, 'templateCreate'])->name('template.create');
            Route::match(['get', 'post'], '/{uid}/template/edit', [CampaignsController::class, 'templateEdit'])->name('template.edit');
            Route::match(['get', 'post'], '/{uid}/template/upload', [CampaignsController::class, 'templateUpload'])->name('template.upload');
            Route::get('/{uid}/template/change/{template_uid}', [CampaignsController::class, 'templateChangeTemplate'])->name('template.change');
            Route::get('/{uid}/template/content', [CampaignsController::class, 'templateContent'])->name('template.content');
            Route::get('/{uid}/template/preview', [CampaignsController::class, 'templatePreview'])->name('template.preview');
            Route::get('/{uid}/template/iframe', [CampaignsController::class, 'templateIframe'])->name('template.iframe');
            Route::get('/{uid}/template/review', [CampaignsController::class, 'templateReview'])->name('template.review');
            Route::get('/{uid}/template/review-iframe', [CampaignsController::class, 'templateReviewIframe'])->name('template.review.iframe');
            Route::get('/{uid}/template/builder-select', [CampaignsController::class, 'templateBuilderSelect'])->name('template.builder.select');
            Route::get('/{uid}/template/build/{style}', [CampaignsController::class, 'templateBuild'])->name('template.build');
            Route::get('/{uid}/template/rebuild', [CampaignsController::class, 'templateRebuild'])->name('template.rebuild');
            Route::get('/{uid}/template/layout/list', [CampaignsController::class, 'templateLayoutList'])->name('template.layout.list');
            Route::match(['get', 'post'], '/{uid}/template/layout', [CampaignsController::class, 'templateLayout'])->name('template.layout');

            // Campaign template builder operations
            Route::match(['get', 'post'], '/{uid}/builder-classic', [CampaignsController::class, 'builderClassic'])->name('builder.classic');
            Route::match(['get', 'post'], '/{uid}/builder-plain', [CampaignsController::class, 'builderPlainEdit'])->name('builder.plain');
            Route::match(['get', 'post'], '/{uid}/plain', [CampaignsController::class, 'plain'])->name('plain');

            // Campaign preheader management
            Route::get('/{uid}/preheader', [CampaignsController::class, 'preheader'])->name('preheader');
            Route::match(['get', 'post'], '/{uid}/preheader/add', [CampaignsController::class, 'preheaderAdd'])->name('preheader.add');
            Route::post('/{uid}/preheader/remove', [CampaignsController::class, 'preheaderRemove'])->name('preheader.remove');

            // Campaign custom content
            Route::post('/{uid}/custom-plain/on', [CampaignsController::class, 'customPlainOn'])->name('custom.plain.on');
            Route::post('/{uid}/custom-plain/off', [CampaignsController::class, 'customPlainOff'])->name('custom.plain.off');

            // Campaign attachments
            Route::post('/{uid}/attachment', [CampaignsController::class, 'uploadAttachment'])->name('attachment.upload');
            Route::post('/{uid}/attachment/remove', [CampaignsController::class, 'removeAttachment'])->name('attachment.remove');
            Route::get('/{uid}/attachment/download', [CampaignsController::class, 'downloadAttachment'])->name('attachment.download');

            // Campaign preview and testing
            Route::get('/{uid}/preview', [CampaignsController::class, 'preview'])->name('preview');
            Route::get('/{uid}/preview/content/{subscriber_uid?}', [CampaignsController::class, 'previewContent'])->name('preview.content');
            Route::get('/{uid}/preview-as', [CampaignsController::class, 'previewAs'])->name('preview.as');
            Route::get('/{uid}/preview-as/list', [CampaignsController::class, 'previewAsList'])->name('preview.as.list');
            Route::match(['get', 'post'], '/send-test-email', [CampaignsController::class, 'sendTestEmail'])->name('send.test');

            // Campaign webhooks management
            Route::get('/{uid}/webhooks', [CampaignsController::class, 'webhooks'])->name('webhooks');
            Route::match(['get', 'post'], '/{uid}/webhooks/add', [CampaignsController::class, 'webhooksAdd'])->name('webhooks.add');
            Route::get('/{uid}/webhooks/list', [CampaignsController::class, 'webhooksList'])->name('webhooks.list');
            Route::get('/{uid}/webhooks/link-select', [CampaignsController::class, 'webhooksLinkSelect'])->name('webhooks.link.select');
            Route::match(['get', 'post'], '/{uid}/webhooks/{webhook_uid}/edit', [CampaignsController::class, 'webhooksEdit'])->name('webhooks.edit');
            Route::match(['get', 'post'], '/{uid}/webhooks/{webhook_uid}/test', [CampaignsController::class, 'webhooksTest'])->name('webhooks.test');
            Route::post('/{uid}/webhooks/{webhook_uid}/test/{message_id}', [CampaignsController::class, 'webhooksTestMessage'])->name('webhooks.test.message');
            Route::post('/{uid}/webhooks/{webhook_uid}/delete', [CampaignsController::class, 'webhooksDelete'])->name('webhooks.delete');
            Route::get('/{uid}/webhooks/{webhook_uid}/sample', [CampaignsController::class, 'webhooksSampleRequest'])->name('webhooks.sample');

            // Campaign tracking and analytics - Subscriber
            Route::get('/{uid}/subscribers', [CampaignsController::class, 'subscribers'])->name('subscribers');
            Route::get('/{uid}/subscribers/listing', [CampaignsController::class, 'subscribersListing'])->name('subscribers.listing');

            // Campaign tracking and analytics - Logs
            Route::get('/{uid}/tracking-log', [CampaignsController::class, 'trackingLog'])->name('tracking.log');
            Route::get('/{uid}/tracking-log/listing', [CampaignsController::class, 'trackingLogListing'])->name('tracking.log.listing');
            Route::get('/{uid}/tracking-log/download', [CampaignsController::class, 'trackingLogDownload'])->name('tracking.log.download');
            Route::get('/{uid}/open-log', [CampaignsController::class, 'openLog'])->name('open.log');
            Route::get('/{uid}/open-log/listing', [CampaignsController::class, 'openLogListing'])->name('open.log.listing');
            Route::get('/{uid}/open-log/{message_id}/execute', [CampaignsController::class, 'openLogExecute'])->name('open.log.execute');
            Route::get('/{uid}/open-map', [CampaignsController::class, 'openMap'])->name('open.map');
            Route::get('/{uid}/click-log', [CampaignsController::class, 'clickLog'])->name('click.log');
            Route::get('/{uid}/click-log/listing', [CampaignsController::class, 'clickLogListing'])->name('click.log.listing');
            Route::get('/{uid}/click-log/{message_id}/execute', [CampaignsController::class, 'clickLogExecute'])->name('click.log.execute');
            Route::get('/{uid}/bounce-log', [CampaignsController::class, 'bounceLog'])->name('bounce.log');
            Route::get('/{uid}/bounce-log/listing', [CampaignsController::class, 'bounceLogListing'])->name('bounce.log.listing');
            Route::get('/{uid}/feedback-log', [CampaignsController::class, 'feedbackLog'])->name('feedback.log');
            Route::get('/{uid}/feedback-log/listing', [CampaignsController::class, 'feedbackLogListing'])->name('feedback.log.listing');
            Route::get('/{uid}/unsubscribe-log', [CampaignsController::class, 'unsubscribeLog'])->name('unsubscribe.log');
            Route::get('/{uid}/unsubscribe-log/listing', [CampaignsController::class, 'unsubscribeLogListing'])->name('unsubscribe.log.listing');

            // Campaign analytics and charts
            Route::get('/{uid}/overview', [CampaignsController::class, 'overview'])->name('overview');
            Route::get('/{uid}/chart', [CampaignsController::class, 'chart'])->name('chart');
            Route::get('/{uid}/chart24h', [CampaignsController::class, 'chart24h'])->name('chart.24h');
            Route::get('/{uid}/chart/countries/open', [CampaignsController::class, 'chartCountry'])->name('chart.country');
            Route::get('/{uid}/chart/countries/click', [CampaignsController::class, 'chartClickCountry'])->name('chart.click.country');
            Route::get('/{uid}/links', [CampaignsController::class, 'links'])->name('links');
            Route::get('/{uid}/spam-score', [CampaignsController::class, 'spamScore'])->name('spam.score');
            Route::get('/{uid}/update-stats', [CampaignsController::class, 'updateStats'])->name('update.stats');

            // Campaign copy and export
            Route::match(['get', 'post'], '/copy', [CampaignsController::class, 'copy'])->name('copy');
            Route::get('/{from_uid}/copy-move-from/{action}', [CampaignsController::class, 'copyMoveForm'])->name('copy.move.form');

            // Campaign deletion
            Route::get('/delete/confirm', [CampaignsController::class, 'deleteConfirm'])->name('delete.confirm');

            // Campaign data exports
            Route::get('/job/{uid}/progress', [CampaignsController::class, 'trackingLogExportProgress'])->name('job.progress');
            Route::get('/job/{uid}/download', [CampaignsController::class, 'download'])->name('job.download');

            // Campaign listing and utilities
            Route::get('/listing/{page?}', [CampaignsController::class, 'listing'])->name('listing');
            Route::get('/quick-view', [CampaignsController::class, 'quickView'])->name('quickView');
            Route::get('/select2', [CampaignsController::class, 'select2'])->name('select2');

        });

        // ===================================================================
        // SEGMENTS ROUTES - Audience segmentation (15+ routes)
        // ===================================================================

        Route::group(['prefix' => 'lists/{list_uid}/segments', 'name' => 'segments.'], function (): void {

            // Segment CRUD operations
            Route::get('/', [SegmentController::class, 'index'])->name('index');
            Route::get('/create', [SegmentController::class, 'create'])->name('create');
            Route::post('/', [SegmentController::class, 'store'])->name('store');
            Route::get('/{uid}/edit', [SegmentController::class, 'edit'])->name('edit');
            Route::patch('/{uid}', [SegmentController::class, 'update'])->name('update');
            Route::delete('/{uid}', [SegmentController::class, 'destroy'])->name('destroy');

            // Segment listing and subscribers
            Route::get('/listing', [SegmentController::class, 'listing'])->name('listing');
            Route::get('/{uid}/subscribers', [SegmentController::class, 'subscribers'])->name('subscribers');
            Route::get('/{uid}/subscribers/listing', [SegmentController::class, 'listingSubscribers'])->name('subscribers.listing');

            // Segment conditions and utilities
            Route::get('/sample-condition', [SegmentController::class, 'sampleCondition'])->name('sample.condition');
            Route::get('/condition-value-control', [SegmentController::class, 'conditionValueControl'])->name('condition.value');
            Route::get('/select-box', [SegmentController::class, 'selectBox'])->name('select.box');
            Route::get('/no-list', [SegmentController::class, 'noList'])->name('no.list');

        });

        // ===================================================================
        // MAILLISTS ROUTES - Subscriber list management (40+ routes)
        // ===================================================================

        Route::group(['prefix' => 'maillists', 'name' => 'maillists.'], function (): void {

            // Maillist CRUD operations
            Route::get('/', [MaillistController::class, 'index'])->name('index');
            Route::get('/create', [MaillistController::class, 'create'])->name('create');
            Route::post('/store', [MaillistController::class, 'store'])->name('store');
            Route::get('/edit/{uid}', [MaillistController::class, 'edit'])->name('edit');
            Route::get('/view/{uid}', [MaillistController::class, 'view'])->name('view');
            Route::get('/destroy/{uid}', [MaillistController::class, 'destroy'])->name('destroy');

            // Maillist update and management
            Route::get('/lists/{uid}/overview', [MaillistController::class, 'overview'])->name('overview');
            Route::get('/lists/{uid}/edit', [MaillistController::class, 'edit'])->name('lists.edit');
            Route::post('/lists/{uid}/update', [MaillistController::class, 'update'])->name('update');
            Route::match(['get', 'post'], '/lists/copy', [MaillistController::class, 'copy'])->name('copy');
            Route::match(['get', 'post'], '/lists/select', [MaillistController::class, 'selectList'])->name('selectList');

            // Maillist verification management
            Route::get('/lists/{uid}/verification', [MaillistController::class, 'verification'])->name('verification');
            Route::post('/lists/{uid}/verification/start', [MaillistController::class, 'startVerification'])->name('startVerification');
            Route::post('/lists/{uid}/verification/{job_uid}/stop', [MaillistController::class, 'stopVerification'])->name('stopVerification');
            Route::post('/lists/{uid}/verification/reset', [MaillistController::class, 'resetVerification'])->name('resetVerification');
            Route::get('/lists/{uid}/verification/{job_uid}/progress', [MaillistController::class, 'verificationProgress'])->name('verificationProgress');

            // Maillist embedded forms
            Route::match(['get', 'post'], '/lists/{uid}/embedded-form', [MaillistController::class, 'embeddedForm'])->name('embeddedForm');
            Route::get('/lists/{uid}/embedded-form-frame', [MaillistController::class, 'embeddedFormFrame'])->name('embeddedFormFrame');
            Route::post('/lists/{uid}/embedded-form-subscribe', [MaillistController::class, 'embeddedFormSubscribe'])->name('embeddedFormSubscribe');
            Route::post('/lists/{uid}/embedded-form-subscribe-captcha', [MaillistController::class, 'embeddedFormSubscribe'])->name('embeddedFormSubscribeCaptcha');

            // Maillist customer cloning
            Route::get('/lists/{uid}/clone-to-customers/choose', [MaillistController::class, 'cloneForCustomersChoose'])->name('cloneForCustomersChoose');
            Route::post('/lists/{uid}/clone-to-customers', [MaillistController::class, 'cloneForCustomers'])->name('cloneForCustomers');

            // Maillist analytics and charts
            Route::get('/lists/{uid}/email-verification/chart', [MaillistController::class, 'emailVerificationChart'])->name('emailVerificationChart');
            Route::get('/lists/{uid}/list-growth', [MaillistController::class, 'listGrowthChart'])->name('listGrowthChart');
            Route::get('/lists/{uid}/list-statistics-chart', [MaillistController::class, 'statisticsChart'])->name('statisticsChart');

            // Maillist listing and deletion
            Route::get('/lists/listing/{page?}', [MaillistController::class, 'listing'])->name('listing');
            Route::get('/lists/sort', [MaillistController::class, 'sort'])->name('sort');
            Route::get('/lists/quick-view', [MaillistController::class, 'quickView'])->name('quickView');
            Route::post('/lists/delete', [MaillistController::class, 'delete'])->name('delete');
            Route::get('/lists/delete/confirm', [MaillistController::class, 'deleteConfirm'])->name('delete.confirm');

            // Email utilities
            Route::get('/lists/{uid}/check-email', [AutomationsController::class, 'checkEmail'])->name('checkEmail');

        });

        // ===================================================================
        // AUTOMATIONS ROUTES - Automated email workflows (35+ routes)
        // ===================================================================

        Route::group(['prefix' => 'automations', 'name' => 'automations.'], function (): void {

            // Automation CRUD and state management
            Route::get('/', [AutomationsController::class, 'index'])->name('index');
            Route::post('/enable', [AutomationsController::class, 'enable'])->name('enable');
            Route::post('/disable', [AutomationsController::class, 'disable'])->name('disable');
            Route::delete('/delete', [AutomationsController::class, 'delete'])->name('delete');
            Route::match(['get', 'post'], 'automation/{uid}/copy', [AutomationsController::class, 'copy'])->name('copy');

            // Automation data and backups
            Route::get('/{uid}/backups', [AutomationsController::class, 'settings'])->name('backups');
            Route::post('/{uid}/update', [AutomationsController::class, 'update'])->name('update');
            Route::post('/{uid}/data/save', [AutomationsController::class, 'saveData'])->name('saveData');
            Route::get('/{uid}/insight', [AutomationsController::class, 'insight'])->name('insight');
            Route::get('/{uid}/last-saved', [AutomationsController::class, 'lastSaved'])->name('lastSaved');

            // Automation trigger and execution
            Route::get('trigger/{id}', [AutomationsController::class, 'show'])->name('show');
            Route::get('/{automation}/{subscriber}/trigger', [AutomationsController::class, 'triggerNow'])->name('triggerNow');
            Route::get('/{automation}/run', [AutomationsController::class, 'run'])->name('run');
            Route::post('/{uid}/trigger-all', [AutomationsController::class, 'triggerAll'])->name('triggerAll');
            Route::get('/{uid}/debug', [AutomationsController::class, 'debug'])->name('debug');

            // Automation email preheader management
            Route::get('/{uid}/template/{email_uid}/preheader', [AutomationsController::class, 'emailPreheader'])->name('emailPreheader');
            Route::match(['get', 'post'], 'automation/{uid}/template/{email_uid}/preheader/add', [AutomationsController::class, 'emailPreheaderAdd'])->name('emailPreheaderAdd');
            Route::post('/{uid}/template/{email_uid}/preheader/remove', [AutomationsController::class, 'emailPreheaderRemove'])->name('emailPreheaderRemove');

            // Automation email testing
            Route::match(['get', 'post'], 'automation/{email_uid}/send-test-email', [AutomationsController::class, 'sendTestEmail'])->name('send.test');

            // Automation conditions management
            Route::get('/{uid}/condition/remove', [AutomationsController::class, 'conditionRemove'])->name('conditionRemove');
            Route::get('/{uid}/condition/setting', [AutomationsController::class, 'conditionSetting'])->name('conditionSetting');
            Route::match(['get', 'post'], 'automation/condition/wait/custom', [AutomationsController::class, 'conditionWaitCustom'])->name('conditionWaitCustom');

            // Automation wait time management
            Route::get('/{uid}/wait-time', [AutomationsController::class, 'waitTime'])->name('waitTime');
            Route::post('/{uid}/wait-time', [AutomationsController::class, 'waitTime'])->name('waitTime.update');

            // Automation operations management
            Route::get('/{uid}/operation/select', [AutomationsController::class, 'operationSelect'])->name('operation.select');
            Route::get('/{uid}/operation/show', [AutomationsController::class, 'operationShow'])->name('operationShow');
            Route::match(['get', 'post'], 'automation/{uid}/operation/create', [AutomationsController::class, 'operationCreate'])->name('operation.create');
            Route::match(['get', 'post'], 'automation/{uid}/operation/edit', [AutomationsController::class, 'operationEdit'])->name('operation.edit');

            // Automation cart management
            Route::get('/{uid}/cart/items', [AutomationsController::class, 'cartItems'])->name('cartItems');
            Route::get('/{uid}/cart/list', [AutomationsController::class, 'cartList'])->name('cartList');
            Route::get('/{uid}/cart/stats', [AutomationsController::class, 'cartStats'])->name('cartStats');
            Route::match(['get', 'post'], 'automation/{uid}/cart/wait', [AutomationsController::class, 'cartWait'])->name('cartWait');
            Route::match(['get', 'post'], 'automation/{uid}/cart/change-store', [AutomationsController::class, 'cartChangeStore'])->name('cartChangeStore');
            Route::match(['get', 'post'], 'automation/{uid}/cart/change-list', [AutomationsController::class, 'cartChangeList'])->name('cartChangeList');

            // Automation subscribers management
            Route::get('/{uid}/subscribers', [AutomationsController::class, 'subscribers'])->name('subscribers.');
            Route::get('/{uid}/subscribers/list', [AutomationsController::class, 'subscribersList'])->name('subscribers.List');
            Route::get('/{uid}/subscribers/{subscriber_uid}/show', [AutomationsController::class, 'subscribersShow'])->name('subscribers.Show');
            Route::post('/{uid}/subscribers/{subscriber_uid}/restart', [AutomationsController::class, 'subscribersRestart'])->name('subscribers.restart');
            Route::post('/{uid}/subscribers/{subscriber_uid}/remove', [AutomationsController::class, 'subscribersRemove'])->name('subscribers.remove');

            // Automation email webhooks management
            Route::get('/emails/{email_uid}/webhooks', [AutomationsController::class, 'webhooks'])->name('webhooks');
            Route::match(['get', 'post'], 'automation/emails/{email_uid}/webhooks/add', [AutomationsController::class, 'webhooksAdd'])->name('webhooksAdd');
            Route::get('/emails/{email_uid}/webhooks/list', [AutomationsController::class, 'webhooksList'])->name('webhooksList');
            Route::get('/emails/{email_uid}/webhooks/link-select', [AutomationsController::class, 'webhooksLinkSelect'])->name('webhooksLinkSelect');
            Route::match(['get', 'post'], 'automation/emails/webhooks/{webhook_uid}/edit', [AutomationsController::class, 'webhooksEdit'])->name('webhooksEdit');
            Route::match(['get', 'post'], 'automation/emails/webhooks/{webhook_uid}/test', [AutomationsController::class, 'webhooksTest'])->name('webhooksTest');
            Route::post('/emails/webhooks/{webhook_uid}/delete', [AutomationsController::class, 'webhooksDelete'])->name('webhooksDelete');
            Route::get('/emails/webhooks/{webhook_uid}/sample/request', [AutomationsController::class, 'webhooksSampleRequest'])->name('webhooksSampleRequest');

            // Automation listing
            Route::get('/listing', [AutomationsController::class, 'listing'])->name('listing');

        });

        // ===================================================================
        // AUTO TRIGGER ROUTES - Automation trigger debugging (2 routes)
        // ===================================================================

        Route::group(['prefix' => 'triggers', 'name' => 'triggers.'], function (): void {
            Route::get('/{id}/show', [AutoTrigger::class, 'show'])->name('show');
            Route::get('/{id}/check', [AutoTrigger::class, 'check'])->name('check');
        });

        // ===================================================================
        // LAYOUTS ROUTES - Email layout templates (9 routes)
        // ===================================================================

        Route::group(['prefix' => 'layouts', 'name' => 'layouts.'], function (): void {

            // Layout CRUD operations
            Route::get('/', [LayoutController::class, 'index'])->name('index');
            Route::get('/create', [LayoutController::class, 'create'])->name('create');
            Route::post('/store', [LayoutController::class, 'store'])->name('store');
            Route::get('/edit/{uid}', [LayoutController::class, 'edit'])->name('edit');
            Route::patch('/update/{uid}', [LayoutController::class, 'update'])->name('update');
            Route::get('/view/{uid}', [LayoutController::class, 'view'])->name('view');
            Route::get('/destroy/{uid}', [LayoutController::class, 'destroy'])->name('destroy');

            // Layout listing and sorting
            Route::get('/listing/{page?}', [LayoutController::class, 'listing'])->name('listing');
            Route::get('/sort', [LayoutController::class, 'sort'])->name('sort');

        });

        // ===================================================================
        // END - Campaign Module Routes (171+ total routes)
        // ===================================================================
    });

<?php

use Illuminate\Support\Facades\Route;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations\AutomationController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations\AutomationFlowController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations\AutomationsController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations\AutoTrigger;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\CampaignProController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\CampaignsController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\DashboardController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\FormController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Funnels\FunnelController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Funnels\FunnelStepController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Layouts\LayoutController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\MaillistController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\MaillistProController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\SegmentController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists\SubscriberController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates\BuilderController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates\CustomerEmailTemplateController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates\PageTemplateController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates\SystemEmailTemplateController;
use Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates\TemplatesController;

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
        'middleware' => ['web', 'auth', 'verified'],
        'prefix' => 'manager',
        'as' => 'manager.',
    ],
    function (): void {

        // ===================================================================
        // SUBSCRIBERS ROUTES - Redirect to maillists (subscribers are list-scoped)
        // ===================================================================
        // Subscribers are managed nested under a specific maillist.
        // These top-level routes redirect to the maillist index so the user
        // can pick a list first.

        Route::group(['prefix' => 'subscribers', 'as' => 'subscribers.'], function (): void {
            Route::get('/', fn () => redirect()->route('manager.maillists.index'))->name('index');
            Route::get('/create', fn () => redirect()->route('manager.maillists.index'))->name('create');
        });

        // ===================================================================
        // TEMPLATES ROUTES - Email template management (30+ routes)
        // ===================================================================

        Route::group(['prefix' => 'templates', 'as' => 'templates.'], function (): void {

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
            Route::get('/validate/{uid}', [TemplatesController::class, 'validateTemplate'])->name('validate');

            // ── EMAIL BUILDER (drag-drop visual) ──────────────────────
            Route::get('/gallery', [BuilderController::class, 'gallery'])->name('gallery');
            Route::post('/from-prebuilt/{key}', [BuilderController::class, 'fromPrebuilt'])->name('builder.from-prebuilt');
            Route::get('/{uid}/builder', [BuilderController::class, 'builder'])->name('builder');
            Route::post('/{uid}/builder/save', [BuilderController::class, 'save'])->name('builder.save');
            Route::post('/{uid}/builder/preview', [BuilderController::class, 'preview'])->name('builder.preview');
            Route::post('/{uid}/builder/send-test', [BuilderController::class, 'sendTest'])->name('builder.send-test');
            Route::post('/builder/render-block', [BuilderController::class, 'renderBlock'])->name('builder.render-block');
            Route::post('/builder/upload-image', [BuilderController::class, 'uploadImage'])->name('builder.upload-image');
            Route::get('/builder/blocks/blank/{type}', [BuilderController::class, 'blockBlank'])->name('builder.block-blank');
            Route::post('/builder/import-html', [BuilderController::class, 'importHtml'])->name('builder.import-html');
            Route::get('/{uid}/builder/export', [BuilderController::class, 'exportHtml'])->name('builder.export');
            Route::post('/{uid}/builder/save-as', [BuilderController::class, 'saveAs'])->name('builder.save-as');
            Route::post('/{uid}/builder/spam-check', [BuilderController::class, 'spamCheck'])->name('builder.spam-check');
            Route::get('/builder/variables', [BuilderController::class, 'variables'])->name('builder.variables');
            Route::post('/{uid}/builder/preview-real', [BuilderController::class, 'previewWithRealData'])->name('builder.preview-real');

            // Template metadata management
            Route::match(['get', 'post'], '/{uid}/name', [TemplatesController::class, 'changeName'])->name('name');
            Route::match(['get', 'post'], '/{uid}/categories', [TemplatesController::class, 'categories'])->name('categories');
            Route::match(['get', 'post'], '/{uid}/thumb', [TemplatesController::class, 'updateThumb'])->name('thumb');
            Route::match(['get', 'post'], '/{uid}/thumb-url', [TemplatesController::class, 'updateThumbUrl'])->name('thumb.url');

            // Template listings
            Route::get('/listing/{page?}', [TemplatesController::class, 'listing'])->name('listing');
            Route::get('/choosing/{campaign_uid}/{page?}', [TemplatesController::class, 'choosing'])->name('choosing');

        });

        // ===================================================================
        // CAMPAIGNS ROUTES - Email campaign management (60+ routes)
        // ===================================================================

        Route::group(['prefix' => 'campaigns', 'as' => 'campaigns.'], function (): void {

            // Campaign CRUD operations
            Route::get('/', [CampaignsController::class, 'index'])->name('index');
            Route::get('/create', [CampaignsController::class, 'create'])->name('create');
            Route::post('/store', [CampaignsController::class, 'store'])->name('store');
            Route::get('/{uid}', [CampaignsController::class, 'show'])->name('show');
            Route::get('/{uid}/edit', [CampaignsController::class, 'edit'])->name('edit');
            Route::patch('/{uid}', [CampaignsController::class, 'update'])->name('update');
            Route::delete('/{uid}', [CampaignsController::class, 'destroy'])->name('destroy');

            // Campaign state management
            Route::post('/{uid}/pause', [CampaignsController::class, 'pause'])->name('pause');
            Route::post('/{uid}/resume', [CampaignsController::class, 'resume'])->name('resume');
            Route::post('/{uid}/restart', [CampaignsController::class, 'restart'])->name('restart');
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

        Route::group(['prefix' => 'lists/{list_uid}/segments', 'as' => 'segments.'], function (): void {

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

        Route::group(['prefix' => 'maillists', 'as' => 'maillists.'], function (): void {

            // Maillist CRUD operations
            Route::get('/', [MaillistController::class, 'index'])->name('index');
            Route::get('/create', [MaillistController::class, 'create'])->name('create');
            Route::post('/store', [MaillistController::class, 'store'])->name('store');
            Route::get('/edit/{uid}', [MaillistController::class, 'edit'])->name('edit');
            Route::get('/view/{uid}', [MaillistController::class, 'view'])->name('view');
            Route::get('/destroy/{uid}', [MaillistController::class, 'destroy'])->name('destroy');

            // ── Slim controller (Phase 1) ──────────────────────────────
            Route::put('/{uid}', [MaillistController::class, 'update'])->name('slim.update');
            Route::get('/{uid}/show', [MaillistController::class, 'show'])->name('show');
            Route::match(['get', 'post'], '/{uid}/sending-servers', [MaillistController::class, 'sendingServers'])->name('sending-servers');
            Route::post('/{uid}/import-csv', [MaillistController::class, 'importCsv'])->name('import-csv');
            Route::post('/{uid}/export', [MaillistController::class, 'export'])->name('export');

            // ── Mapping de fields (variables del builder) ──────────────
            Route::get('/{uid}/fields', [MaillistController::class, 'fields'])->name('fields');
            Route::post('/{uid}/fields', [MaillistController::class, 'fieldsStore'])->name('fields.store');
            Route::patch('/{uid}/fields/{fieldId}', [MaillistController::class, 'fieldsUpdate'])->name('fields.update');
            Route::delete('/{uid}/fields/{fieldId}', [MaillistController::class, 'fieldsDestroy'])->name('fields.destroy');

            // ── Subscribers anidados bajo lista ────────────────────────
            Route::group(['prefix' => '{listUid}/subscribers', 'as' => 'subscribers.'], function (): void {
                Route::get('/', [SubscriberController::class, 'index'])->name('index');
                Route::get('/create', [SubscriberController::class, 'create'])->name('create');
                Route::post('/', [SubscriberController::class, 'store'])->name('store');
                Route::get('/{subUid}/edit', [SubscriberController::class, 'edit'])->name('edit');
                Route::put('/{subUid}', [SubscriberController::class, 'update'])->name('update');
                Route::delete('/{subUid}', [SubscriberController::class, 'destroy'])->name('destroy');
                Route::post('/{subUid}/tags', [SubscriberController::class, 'updateTags'])->name('tags.update');
            });

            // ── Segmentos anidados bajo lista ──────────────────────────
            Route::group(['prefix' => '{listUid}/segments', 'as' => 'segments.'], function (): void {
                Route::get('/', [SegmentController::class, 'index'])->name('index');
                Route::get('/create', [SegmentController::class, 'create'])->name('create');
                Route::post('/', [SegmentController::class, 'store'])->name('store');
                Route::get('/{segmentUid}/edit', [SegmentController::class, 'edit'])->name('edit');
                Route::put('/{segmentUid}', [SegmentController::class, 'update'])->name('update');
                Route::delete('/{segmentUid}', [SegmentController::class, 'destroy'])->name('destroy');
            });

            // Maillist update and management
            Route::get('/lists/{uid}/overview', [MaillistController::class, 'overview'])->name('overview');
            Route::get('/lists/{uid}/edit', [MaillistController::class, 'edit'])->name('lists.edit');
            Route::post('/lists/{uid}/update', [MaillistController::class, 'update'])->name('update');
            // Legacy routes removed (methods don't exist in MaillistController)

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

            // Maillist deletion (only existing methods)
            Route::post('/lists/delete', [MaillistController::class, 'delete'])->name('delete');

            // Email utilities
            Route::get('/lists/{uid}/check-email', [AutomationsController::class, 'checkEmail'])->name('checkEmail');

        });

        // ===================================================================
        // AUTOMATIONS ROUTES - Automated email workflows (35+ routes)
        // ===================================================================

        Route::group(['prefix' => 'automations', 'as' => 'automations.'], function (): void {

            // Automation CRUD and state management
            Route::get('/', [AutomationsController::class, 'index'])->name('index');
            Route::get('/create', [AutomationsController::class, 'create'])->name('create');
            Route::post('/store', [AutomationsController::class, 'store'])->name('store');
            Route::get('/{uid}/edit', [AutomationsController::class, 'edit'])->name('edit');
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
            // Route removed: conditionWaitCustom method does not exist

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

            // Route removed: listing method does not exist

        });

        // ===================================================================
        // AUTO TRIGGER ROUTES - Automation trigger debugging (2 routes)
        // ===================================================================

        Route::group(['prefix' => 'triggers', 'as' => 'triggers.'], function (): void {
            Route::get('/{id}/show', [AutoTrigger::class, 'show'])->name('show');
            Route::get('/{id}/check', [AutoTrigger::class, 'check'])->name('check');
        });

        // ===================================================================
        // LAYOUTS ROUTES - Email layout templates (9 routes)
        // ===================================================================

        Route::group(['prefix' => 'layouts', 'as' => 'layouts.'], function (): void {

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
        // PAGE TEMPLATES - Builder BuilderJS portado de acellemail
        // ===================================================================

        Route::group(['prefix' => 'page-templates', 'as' => 'page_templates.'], function (): void {
            // Rutas estáticas (antes de las dinámicas {uid})
            Route::get('/', [PageTemplateController::class, 'index'])->name('index');
            Route::get('/listing', [PageTemplateController::class, 'listing'])->name('listing');
            Route::get('/gallery', [PageTemplateController::class, 'gallery'])->name('gallery');
            Route::get('/add', [PageTemplateController::class, 'add'])->name('add');
            Route::post('/store', [PageTemplateController::class, 'store'])->name('store');
            Route::post('/delete', [PageTemplateController::class, 'delete'])->name('delete');

            // Endpoints auxiliares del builder
            Route::post('/asset-upload', [PageTemplateController::class, 'assetUpload'])->name('asset_upload');
            Route::post('/export/html', [PageTemplateController::class, 'exportHtml'])->name('export_html');
            Route::post('/export/zip', [PageTemplateController::class, 'exportZip'])->name('export_zip');
            Route::get('/rss-proxy', [PageTemplateController::class, 'rssProxy'])->name('rss_proxy');
            Route::get('/theme-assets/{theme}/{path?}', [PageTemplateController::class, 'themeAsset'])
                ->where('path', '.*')->name('theme_asset');
            Route::get('/thumb/{uid}', [PageTemplateController::class, 'thumbnail'])->name('thumb');

            // Endpoints de guardado del builder (POST de builder.default)
            Route::post('/{uid}/builder-edit', [PageTemplateController::class, 'builderEdit'])->name('builder_edit');
            Route::post('/{uid}/change-template', [PageTemplateController::class, 'changeTemplate'])->name('change_template');

            // Rutas dinámicas
            Route::get('/{uid}/preview', [PageTemplateController::class, 'preview'])->name('preview');
            Route::get('/{uid}/copy', [PageTemplateController::class, 'copy'])->name('copy');
            Route::post('/{uid}/store-copy', [PageTemplateController::class, 'storeCopy'])->name('store_copy');
            Route::get('/{uid}/change-name', [PageTemplateController::class, 'changeName'])->name('change_name');
            Route::post('/{uid}/update-name', [PageTemplateController::class, 'updateName'])->name('update_name');
            Route::match(['get', 'post'], '/{uid}/builder', [PageTemplateController::class, 'builder'])->name('builder');
            Route::match(['get', 'post'], '/{uid}/custom-html', [PageTemplateController::class, 'customHtml'])->name('custom_html');
        });

        // ===================================================================
        // SYSTEM EMAIL TEMPLATES - Builder BuilderJS (kind=email)
        // Reutiliza los endpoints genéricos del builder del grupo page_templates
        // (builder-edit, change-template, asset-upload, export, theme-assets, thumb).
        // ===================================================================

        Route::group(['prefix' => 'email-templates', 'as' => 'email_templates.'], function (): void {
            Route::get('/', [SystemEmailTemplateController::class, 'index'])->name('index');
            Route::get('/listing', [SystemEmailTemplateController::class, 'listing'])->name('listing');
            Route::get('/gallery', [SystemEmailTemplateController::class, 'gallery'])->name('gallery');
            Route::get('/add', [SystemEmailTemplateController::class, 'add'])->name('add');
            Route::post('/store', [SystemEmailTemplateController::class, 'store'])->name('store');
            Route::post('/delete', [SystemEmailTemplateController::class, 'delete'])->name('delete');

            Route::get('/{uid}/preview', [SystemEmailTemplateController::class, 'preview'])->name('preview');
            Route::get('/{uid}/copy', [SystemEmailTemplateController::class, 'copy'])->name('copy');
            Route::post('/{uid}/store-copy', [SystemEmailTemplateController::class, 'storeCopy'])->name('store_copy');
            Route::get('/{uid}/change-name', [SystemEmailTemplateController::class, 'changeName'])->name('change_name');
            Route::post('/{uid}/update-name', [SystemEmailTemplateController::class, 'updateName'])->name('update_name');
            Route::match(['get', 'post'], '/{uid}/builder', [SystemEmailTemplateController::class, 'builder'])->name('builder');
            Route::match(['get', 'post'], '/{uid}/custom-html', [SystemEmailTemplateController::class, 'customHtml'])->name('custom_html');
        });

        // ===================================================================
        // MY EMAIL TEMPLATES (cliente) - plantillas de trabajo del usuario
        // ===================================================================

        Route::group(['prefix' => 'my-email-templates', 'as' => 'my_email_templates.'], function (): void {
            Route::get('/', [CustomerEmailTemplateController::class, 'index'])->name('index');
            Route::get('/listing', [CustomerEmailTemplateController::class, 'listing'])->name('listing');
            Route::get('/gallery', [CustomerEmailTemplateController::class, 'gallery'])->name('gallery');
            Route::get('/add', [CustomerEmailTemplateController::class, 'add'])->name('add');
            Route::post('/store', [CustomerEmailTemplateController::class, 'store'])->name('store');
            Route::post('/delete', [CustomerEmailTemplateController::class, 'delete'])->name('delete');

            Route::get('/{uid}/preview', [CustomerEmailTemplateController::class, 'preview'])->name('preview');
            // Nombres alineados con las vistas portadas (change_name/update_name/copy/store_copy).
            Route::get('/{uid}/copy', [CustomerEmailTemplateController::class, 'copy'])->name('copy');
            Route::post('/{uid}/store-copy', [CustomerEmailTemplateController::class, 'copy'])->name('store_copy');
            Route::get('/{uid}/change-name', [CustomerEmailTemplateController::class, 'rename'])->name('change_name');
            Route::post('/{uid}/update-name', [CustomerEmailTemplateController::class, 'rename'])->name('update_name');
            Route::post('/{uid}/change-template', [CustomerEmailTemplateController::class, 'changeTemplate'])->name('change_template');
            Route::match(['get', 'post'], '/{uid}/builder', [CustomerEmailTemplateController::class, 'builder'])->name('builder');
            Route::match(['get', 'post'], '/{uid}/custom-html', [CustomerEmailTemplateController::class, 'customHtml'])->name('custom_html');
        });

        // ===================================================================
        // FLOW AUTOMATIONS (DAG) - portado de acellemail (Automation2)
        // Convive con las automatizaciones legacy del módulo (manager.automations.*).
        // ===================================================================

        Route::group(['prefix' => 'flow-automations', 'as' => 'flow_automations.'], function (): void {
            Route::get('/', [AutomationController::class, 'index'])->name('index');
            Route::get('/listing', [AutomationController::class, 'listing'])->name('listing');
            Route::post('/delete', [AutomationController::class, 'delete'])->name('delete');
            Route::get('/create', [AutomationController::class, 'create'])->name('create');
            Route::post('/create', [AutomationController::class, 'store'])->name('store');
            Route::get('/create/options', [AutomationController::class, 'triggerOptions'])->name('trigger_options');
            Route::get('/segments', [AutomationController::class, 'segmentSelect'])->name('segment_select');
            Route::get('/{uid}/edit', [AutomationController::class, 'edit'])->name('edit');

            // Editor de flujo (grafo DAG) — endpoints REST
            Route::get('/{uid}/flow', [AutomationFlowController::class, 'show'])->name('flow.show');
            Route::post('/{uid}/nodes/new/{type}', [AutomationFlowController::class, 'createNode'])
                ->where('type', 'send|wait|wait_until|condition|operation|webhook')->name('nodes.create');
            Route::patch('/{uid}/nodes/{nodeId}', [AutomationFlowController::class, 'updateNode'])->name('nodes.update');
            Route::delete('/{uid}/nodes/{nodeId}', [AutomationFlowController::class, 'deleteNode'])->name('nodes.delete');
            Route::patch('/{uid}/trigger', [AutomationFlowController::class, 'updateTrigger'])->name('trigger.update');
            Route::post('/{uid}/enable', [AutomationController::class, 'enable'])->name('enable');
            Route::post('/{uid}/disable', [AutomationController::class, 'disable'])->name('disable');
        });

        // ===================================================================
        // CAMPAIGNS PRO - UI rica portada de acellemail (asistente)
        // Convive con manager.campaigns.* (campañas existentes del módulo).
        // ===================================================================

        Route::group(['prefix' => 'campaigns-pro', 'as' => 'campaigns_pro.'], function (): void {
            // ── Rutas estáticas (sin {uid}) — deben ir ANTES de las dinámicas ──
            Route::get('/', [CampaignProController::class, 'index'])->name('index');
            Route::get('/listing', [CampaignProController::class, 'listing'])->name('listing');
            Route::get('/select-type', [CampaignProController::class, 'selectType'])->name('select_type');
            Route::post('/create', [CampaignProController::class, 'create'])->name('create');
            Route::post('/bulk-delete', [CampaignProController::class, 'deleteCampaign'])->name('bulk_delete');

            // ── Asistente multi-paso (Increment 2) ────────────────────────────
            Route::get('/{uid}/recipients', [CampaignProController::class, 'recipients'])->name('recipients');
            Route::post('/{uid}/recipients', [CampaignProController::class, 'recipients'])->name('recipients.save');
            Route::get('/{uid}/setup', [CampaignProController::class, 'setup'])->name('setup');
            Route::post('/{uid}/setup', [CampaignProController::class, 'setup'])->name('setup.save');
            Route::get('/{uid}/schedule', [CampaignProController::class, 'schedule'])->name('schedule');
            Route::post('/{uid}/schedule', [CampaignProController::class, 'schedule'])->name('schedule.save');
            Route::get('/{uid}/confirm', [CampaignProController::class, 'confirm'])->name('confirm');
            Route::post('/{uid}/confirm', [CampaignProController::class, 'confirm'])->name('confirm.send');

            // ── Overview y acciones ────────────────────────────────────────────
            Route::get('/{uid}/overview', [CampaignProController::class, 'overview'])->name('overview');
            Route::post('/{uid}/pause', [CampaignProController::class, 'pause'])->name('pause');
            Route::post('/{uid}/resume', [CampaignProController::class, 'resume'])->name('resume');
            Route::get('/{uid}/preview', [CampaignProController::class, 'preview'])->name('preview');
        });

        // ===================================================================
        // FORMS - formularios de suscripción (builder kind=form)
        // ===================================================================

        Route::group(['prefix' => 'forms', 'as' => 'forms.'], function (): void {
            Route::get('/', [FormController::class, 'index'])->name('index');
            Route::get('/listing', [FormController::class, 'listing'])->name('listing');
            Route::match(['get', 'post'], '/create', [FormController::class, 'create'])->name('create');
            Route::post('/delete', [FormController::class, 'delete'])->name('delete');
            Route::post('/publish', [FormController::class, 'publish'])->name('publish');
            Route::post('/unpublish', [FormController::class, 'unpublish'])->name('unpublish');
            Route::get('/{uid}/preview', [FormController::class, 'preview'])->name('preview');
            Route::match(['get', 'post'], '/{uid}/builder', [FormController::class, 'builder'])->name('builder');
            Route::post('/{uid}/builder-save', [FormController::class, 'builder'])->name('builder_save');
            Route::post('/{uid}/settings', [FormController::class, 'settings'])->name('settings');
        });

        // ===================================================================
        // FUNNELS - embudos de páginas (pasos editados con builder kind=page)
        // ===================================================================

        Route::group(['prefix' => 'funnels', 'as' => 'funnels.'], function (): void {
            Route::get('/', [FunnelController::class, 'index'])->name('index');
            Route::get('/listing', [FunnelController::class, 'listing'])->name('listing');
            Route::get('/create', [FunnelController::class, 'create'])->name('create');
            Route::post('/store', [FunnelController::class, 'store'])->name('store');
            Route::post('/delete', [FunnelController::class, 'delete'])->name('delete');
            Route::get('/{uid}/edit', [FunnelController::class, 'edit'])->name('edit');
            Route::match(['patch', 'post'], '/{uid}/update', [FunnelController::class, 'update'])->name('update');
            Route::post('/{uid}/publish', [FunnelController::class, 'publish'])->name('publish');
            Route::post('/{uid}/unpublish', [FunnelController::class, 'unpublish'])->name('unpublish');
            Route::post('/{uid}/duplicate', [FunnelController::class, 'duplicate'])->name('duplicate');

            // Pasos
            Route::group(['prefix' => 'steps', 'as' => 'steps.'], function (): void {
                Route::post('/{funnelUid}/store', [FunnelStepController::class, 'store'])->name('store');
                Route::post('/{funnelUid}/reorder', [FunnelStepController::class, 'reorder'])->name('reorder');
                Route::get('/{uid}/template-gallery', [FunnelStepController::class, 'templateGallery'])->name('template_gallery');
                Route::match(['get', 'post'], '/{uid}/builder', [FunnelStepController::class, 'builder'])->name('builder');
                Route::get('/{uid}/preview', [FunnelStepController::class, 'preview'])->name('preview');
                Route::post('/{uid}/duplicate', [FunnelStepController::class, 'duplicate'])->name('duplicate');
                Route::delete('/{uid}', [FunnelStepController::class, 'delete'])->name('delete');
            });
        });

        // ===================================================================
        // DASHBOARD - panel con métricas globales del módulo
        // ===================================================================

        Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/chart-data', [DashboardController::class, 'chartData'])->name('chart_data');
        });

        // ===================================================================
        // LISTS PRO - UI rica portada de acellemail para listas y suscriptores
        // Convive con manager.maillists.* (listas existentes del módulo).
        // ===================================================================

        Route::group(['prefix' => 'lists-pro', 'as' => 'lists_pro.'], function (): void {
            Route::get('/', [MaillistProController::class, 'index'])->name('index');
            Route::get('/listing', [MaillistProController::class, 'listing'])->name('listing');
            Route::get('/{uid}/overview', [MaillistProController::class, 'overview'])->name('overview');
            Route::get('/{uid}/subscribers', [MaillistProController::class, 'subscribers'])->name('subscribers');
            Route::get('/{uid}/subscribers/listing', [MaillistProController::class, 'subscribersListing'])->name('subscribers_listing');
            Route::get('/{uid}/subscribers/create', [MaillistProController::class, 'subscriberCreate'])->name('subscriber_create');
            Route::post('/{uid}/subscribers/store', [MaillistProController::class, 'subscriberStore'])->name('subscriber_store');
            Route::get('/{uid}/subscribers/{subUid}/edit', [MaillistProController::class, 'subscriberEdit'])->name('subscriber_edit');
            Route::post('/{uid}/subscribers/{subUid}/update', [MaillistProController::class, 'subscriberUpdate'])->name('subscriber_update');
            Route::post('/{uid}/subscribers/{subUid}/delete', [MaillistProController::class, 'subscriberDelete'])->name('subscriber_delete');
            Route::post('/{uid}/subscribers/bulk-delete', [MaillistProController::class, 'subscriberBulkDelete'])->name('subscriber_bulk_delete');
        });

        // ===================================================================
        // END - Campaign Module Routes (171+ total routes)
        // ===================================================================
    });

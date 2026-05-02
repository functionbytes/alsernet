<?php

use Illuminate\Support\Facades\Route;
use Modules\Social\Http\Controllers\AbTestController;
use Modules\Social\Http\Controllers\AccountController;
use Modules\Social\Http\Controllers\AIContentController;
use Modules\Social\Http\Controllers\AnalyticsController;
use Modules\Social\Http\Controllers\ApprovalController;
use Modules\Social\Http\Controllers\BestTimeController;
use Modules\Social\Http\Controllers\BulkImportController;
use Modules\Social\Http\Controllers\CalendarController;
use Modules\Social\Http\Controllers\CampaignController;
use Modules\Social\Http\Controllers\ExportController;
use Modules\Social\Http\Controllers\HashtagGroupController;
use Modules\Social\Http\Controllers\HealthCheckController;
use Modules\Social\Http\Controllers\InboxController;
use Modules\Social\Http\Controllers\LabelController;
use Modules\Social\Http\Controllers\MediaLibraryController;
use Modules\Social\Http\Controllers\NotificationController;
use Modules\Social\Http\Controllers\OAuthController;
use Modules\Social\Http\Controllers\PerformanceInsightsController;
use Modules\Social\Http\Controllers\PublishingController;
use Modules\Social\Http\Controllers\RssFeedController;
use Modules\Social\Http\Controllers\ShortUrlController;
use Modules\Social\Http\Controllers\SocialListeningController;
use Modules\Social\Http\Controllers\TemplateController;
use Modules\Social\Http\Controllers\Webhooks\FacebookWebhookController;
use Modules\Social\Http\Controllers\Webhooks\InstagramWebhookController;
use Modules\Social\Http\Controllers\Webhooks\LinkedInWebhookController;
use Modules\Social\Http\Controllers\Webhooks\TwitterWebhookController;

/*
|--------------------------------------------------------------------------
| Social Module Routes
|--------------------------------------------------------------------------
|
| Social media management routes for multi-channel publishing
|
*/

Route::middleware(['web', 'auth'])->prefix('admin/social')->name('admin.social.')->group(function () {

    // Dashboard
    Route::get('/', [PublishingController::class, 'index'])->name('index');

    // Account Management
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::get('/select', [AccountController::class, 'select'])->name('select');
        Route::post('/save-selected', [AccountController::class, 'saveSelected'])->name('save-selected');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        Route::get('/{account}/reconnect', [AccountController::class, 'reconnect'])->name('reconnect');
        Route::post('/{account}/sync', [AccountController::class, 'sync'])->name('sync');
    });

    // OAuth - Social Network Authentication
    Route::prefix('oauth')->name('oauth.')->group(function () {
        Route::get('/{network}/redirect', [OAuthController::class, 'redirect'])->name('redirect');
        Route::get('/{network}/callback', [OAuthController::class, 'callback'])->name('callback');
    });

    // Publishing
    Route::prefix('publishing')->name('publishing.')->group(function () {
        Route::get('/', [PublishingController::class, 'index'])->name('index');
        Route::get('/calendar', [PublishingController::class, 'calendar'])->name('calendar');
        Route::get('/create', [PublishingController::class, 'create'])->name('create');
        Route::post('/', [PublishingController::class, 'store'])->name('store');
        Route::get('/{post}/edit', [PublishingController::class, 'edit'])->name('edit');
        Route::put('/{post}', [PublishingController::class, 'update'])->name('update');
        Route::delete('/{post}', [PublishingController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/publish', [PublishingController::class, 'publish'])->name('publish');
        Route::post('/{post}/duplicate', [PublishingController::class, 'duplicate'])->name('duplicate');
    });

    // Campaigns
    Route::resource('campaigns', CampaignController::class);

    // Labels
    Route::resource('labels', LabelController::class)->except(['show']);

    // Media Library
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaLibraryController::class, 'index'])->name('index');
        Route::post('/upload', [MediaLibraryController::class, 'upload'])->name('upload');
        Route::delete('/', [MediaLibraryController::class, 'destroy'])->name('destroy');
    });

    // Templates
    Route::resource('templates', TemplateController::class)->except(['show']);
    Route::post('/templates/{template}/apply', [TemplateController::class, 'apply'])->name('templates.apply');

    // Hashtag Groups
    Route::resource('hashtags', HashtagGroupController::class)->except(['show']);

    // Short URLs
    Route::get('/short-urls', [ShortUrlController::class, 'index'])->name('short-urls.index');
    Route::post('/short-urls', [ShortUrlController::class, 'store'])->name('short-urls.store');
    Route::delete('/short-urls/{shortUrl}', [ShortUrlController::class, 'destroy'])->name('short-urls.destroy');

    // Bulk Import
    Route::prefix('bulk-import')->name('bulk-import.')->group(function () {
        Route::get('/', [BulkImportController::class, 'index'])->name('index');
        Route::get('/create', [BulkImportController::class, 'create'])->name('create');
        Route::post('/', [BulkImportController::class, 'store'])->name('store');
        Route::get('/{bulkImport}', [BulkImportController::class, 'show'])->name('show');
        Route::delete('/{bulkImport}', [BulkImportController::class, 'destroy'])->name('destroy');
    });

    // RSS Feeds
    Route::resource('rss-feeds', RssFeedController::class);
    Route::post('/rss-feeds/{rssFeed}/toggle', [RssFeedController::class, 'toggle'])->name('rss-feeds.toggle');

    // A/B Tests
    Route::resource('ab-tests', AbTestController::class)->except(['edit', 'update']);
    Route::post('/ab-tests/{abTest}/complete', [AbTestController::class, 'complete'])->name('ab-tests.complete');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // AI Content Generator
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('/generate', [AIContentController::class, 'generate'])->name('generate');
        Route::post('/hashtags', [AIContentController::class, 'suggestHashtags'])->name('hashtags');
        Route::post('/improve', [AIContentController::class, 'improve'])->name('improve');
        Route::post('/variations', [AIContentController::class, 'generateVariations'])->name('variations');
    });

    // Exports
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/posts/excel', [ExportController::class, 'exportPostsExcel'])->name('posts.excel');
        Route::get('/posts/pdf', [ExportController::class, 'exportPostsPDF'])->name('posts.pdf');
        Route::get('/analytics/pdf', [ExportController::class, 'exportAnalyticsPDF'])->name('analytics.pdf');
    });

    // Health Check & System Status
    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/', [HealthCheckController::class, 'index'])->name('index');
        Route::get('/check', [HealthCheckController::class, 'check'])->name('check');
        Route::get('/check/{component}', [HealthCheckController::class, 'checkComponent'])->name('check-component');
        Route::post('/refresh-token/{accountId}', [HealthCheckController::class, 'refreshToken'])->name('refresh-token');
        Route::post('/retry-failed-jobs', [HealthCheckController::class, 'retryFailedJobs'])->name('retry-failed-jobs');
        Route::post('/clear-failed-jobs', [HealthCheckController::class, 'clearFailedJobs'])->name('clear-failed-jobs');
    });

    // Post Approval Workflow
    Route::prefix('approval')->name('approval.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::post('/{post}/submit', [ApprovalController::class, 'submit'])->name('submit');
        Route::post('/{post}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{post}/reject', [ApprovalController::class, 'reject'])->name('reject');
        Route::post('/{post}/return-to-draft', [ApprovalController::class, 'returnToDraft'])->name('return-to-draft');
        Route::post('/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-reject', [ApprovalController::class, 'bulkReject'])->name('bulk-reject');
    });

    // Unified Inbox
    Route::prefix('inbox')->name('inbox.')->group(function () {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::get('/archived', [InboxController::class, 'archived'])->name('archived');
        Route::post('/{mention}/mark-as-read', [InboxController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/{mention}/mark-as-unread', [InboxController::class, 'markAsUnread'])->name('mark-as-unread');
        Route::post('/{mention}/archive', [InboxController::class, 'archive'])->name('archive');
        Route::post('/{mention}/unarchive', [InboxController::class, 'unarchive'])->name('unarchive');
        Route::post('/{mention}/reply', [InboxController::class, 'reply'])->name('reply');
        Route::post('/bulk-mark-as-read', [InboxController::class, 'bulkMarkAsRead'])->name('bulk-mark-as-read');
        Route::post('/bulk-archive', [InboxController::class, 'bulkArchive'])->name('bulk-archive');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/count', [NotificationController::class, 'count'])->name('count');
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/all', [NotificationController::class, 'all'])->name('all');
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Best Time to Post AI
    Route::prefix('best-time')->name('best-time.')->group(function () {
        Route::get('/', [BestTimeController::class, 'index'])->name('index');
        Route::get('/network/{network}', [BestTimeController::class, 'getForNetwork'])->name('network');
        Route::get('/account/{account}', [BestTimeController::class, 'getForAccount'])->name('account');
        Route::post('/suggest/{account}', [BestTimeController::class, 'suggestNextTime'])->name('suggest');
    });

    // Social Listening
    Route::prefix('listening')->name('listening.')->group(function () {
        Route::get('/', [SocialListeningController::class, 'index'])->name('index');
        Route::get('/create', [SocialListeningController::class, 'create'])->name('create');
        Route::post('/', [SocialListeningController::class, 'store'])->name('store');
        Route::get('/{keyword}/edit', [SocialListeningController::class, 'edit'])->name('edit');
        Route::put('/{keyword}', [SocialListeningController::class, 'update'])->name('update');
        Route::delete('/{keyword}', [SocialListeningController::class, 'destroy'])->name('destroy');
        Route::post('/{keyword}/toggle', [SocialListeningController::class, 'toggle'])->name('toggle');
        Route::post('/{keyword}/scan', [SocialListeningController::class, 'scan'])->name('scan');
        Route::get('/{keyword}/mentions', [SocialListeningController::class, 'mentions'])->name('mentions');
        Route::post('/scan-all', [SocialListeningController::class, 'scanAll'])->name('scan-all');
    });

    // Performance Insights
    Route::prefix('insights')->name('insights.')->group(function () {
        Route::get('/', [PerformanceInsightsController::class, 'index'])->name('index');
        Route::get('/data', [PerformanceInsightsController::class, 'getData'])->name('data');
        Route::get('/export', [PerformanceInsightsController::class, 'export'])->name('export');
    });

    // Calendar
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/events', [CalendarController::class, 'getEvents'])->name('events');
        Route::put('/events/{post}', [CalendarController::class, 'updateEvent'])->name('update-event');
        Route::post('/events/{post}/action', [CalendarController::class, 'quickAction'])->name('quick-action');
    });
});

// Public URL shortener redirect (outside auth middleware)
Route::get('/s/{code}', [ShortUrlController::class, 'redirect'])->name('short-url.redirect');

/*
|--------------------------------------------------------------------------
| Webhook Routes (Public - No Auth)
|--------------------------------------------------------------------------
|
| These routes receive webhook notifications from social networks
| Signature verification is done in the controllers
|
*/

Route::prefix('webhooks/social')->name('webhooks.social.')->group(function () {
    // Facebook Webhooks (GET for verification, POST for events)
    Route::match(['get', 'post'], '/facebook', [FacebookWebhookController::class, 'handle'])
        ->name('facebook');

    // Instagram Webhooks (GET for verification, POST for events)
    Route::match(['get', 'post'], '/instagram', [InstagramWebhookController::class, 'handle'])
        ->name('instagram');

    // Twitter Webhooks (GET for CRC, POST for events)
    Route::match(['get', 'post'], '/twitter', [TwitterWebhookController::class, 'handle'])
        ->name('twitter');

    // LinkedIn Webhooks (POST only)
    Route::post('/linkedin', [LinkedInWebhookController::class, 'handle'])
        ->name('linkedin');
});

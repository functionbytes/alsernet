<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\CloudImportController;
use Modules\Media\Http\Controllers\FolderTemplateController;
use Modules\Media\Http\Controllers\MediaCommentController;
use Modules\Media\Http\Controllers\MediaController;
use Modules\Media\Http\Controllers\MediaEmbedController;
use Modules\Media\Http\Controllers\MediaFileController;
use Modules\Media\Http\Controllers\MediaFolderController;
use Modules\Media\Http\Controllers\MediaLockController;
use Modules\Media\Http\Controllers\MediaShareController;
use Modules\Media\Http\Controllers\MediaTagController;
use Modules\Media\Http\Controllers\MediaWorkflowController;
use Modules\Media\Http\Controllers\ModerationController;
use Modules\Media\Http\Controllers\PublicMediaController;
use Modules\Media\Http\Controllers\WebDavController;

// Public indirect URL (throttled, no auth required)
Route::middleware(['web', 'throttle:60,1'])
    ->get('media/files/{hash}/{id}', [PublicMediaController::class, 'show'])
    ->name('media.indirect.url');

// Public share link (token-based, throttled, no auth required)
Route::middleware(['web', 'throttle:60,1'])
    ->get('media/share/{token}', [PublicMediaController::class, 'showShared'])
    ->name('media.share');

// Media manager - authenticated
Route::middleware(['web', 'auth'])->prefix('panel/media')->name('media.')->group(function (): void {
    // Main view & listing
    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::get('/list', [MediaController::class, 'getList'])->name('list');
    Route::get('/quota', [MediaController::class, 'getQuotaUsage'])->name('quota');
    Route::get('/search', [MediaController::class, 'search'])->name('search');
    Route::get('/breadcrumbs', [MediaController::class, 'getBreadcrumbs'])->name('breadcrumbs');
    Route::post('/set-disk', [MediaController::class, 'setActiveDisk'])->name('set-disk');
    Route::delete('/trash/empty', [MediaController::class, 'emptyTrash'])->name('trash.empty');
    Route::post('/bulk-action', [MediaController::class, 'bulkAction'])->name('bulk-action');
    Route::post('/bulk-download', [MediaController::class, 'bulkDownload'])->name('bulk-download');
    Route::get('/duplicates', [MediaController::class, 'duplicates'])->name('duplicates');

    // File operations
    Route::prefix('files')->name('files.')->group(function (): void {
        Route::post('/upload', [MediaFileController::class, 'upload'])->name('upload');
        Route::post('/upload-url', [MediaFileController::class, 'uploadFromUrl'])->name('upload-url');
        Route::post('/chunk', [MediaFileController::class, 'uploadChunk'])->name('chunk');
        Route::post('/chunk/complete', [MediaFileController::class, 'completeChunkUpload'])->name('chunk.complete');
        Route::delete('/chunk/{uploadId}', [MediaFileController::class, 'abortChunkUpload'])->name('chunk.abort');
        Route::put('/{file}/rename', [MediaFileController::class, 'rename'])->name('rename');
        Route::post('/{file}/copy', [MediaFileController::class, 'copy'])->name('copy');
        Route::delete('/{file}', [MediaFileController::class, 'delete'])->name('delete');
        Route::post('/{file}/restore', [MediaFileController::class, 'restore'])->name('restore')->withTrashed();
        Route::put('/{file}/move', [MediaFileController::class, 'move'])->name('move');
        Route::post('/{file}/toggle-favorite', [MediaFileController::class, 'toggleFavorite'])->name('toggle-favorite');
        Route::post('/{file}/share', [MediaFileController::class, 'createShareLink'])->name('share');
        Route::get('/{file}/similar', [MediaFileController::class, 'similar'])->name('similar');
        Route::post('/presigned-url', [MediaFileController::class, 'presignedUpload'])->name('presigned-url');
        Route::post('/presigned-complete', [MediaFileController::class, 'presignedComplete'])->name('presigned-complete');
        Route::get('/{file}/versions', [MediaFileController::class, 'versions'])->name('versions');
        Route::post('/{file}/versions/{version}/restore', [MediaFileController::class, 'restoreVersion'])->name('versions.restore');
        Route::get('/{file}/access-logs', [MediaFileController::class, 'accessLogs'])->name('access-logs');
        Route::put('/{file}/expiration', [MediaFileController::class, 'setExpiration'])->name('expiration');
        Route::post('/{file}/share/revoke', [MediaFileController::class, 'revokeShareLink'])->name('share.revoke');
    });

    // Folder operations
    Route::prefix('folders')->name('folders.')->group(function (): void {
        Route::post('/create', [MediaFolderController::class, 'store'])->name('create');
        Route::put('/{folder}/rename', [MediaFolderController::class, 'rename'])->name('rename');
        Route::delete('/{folder}', [MediaFolderController::class, 'delete'])->name('delete');
        Route::post('/{folder}/restore', [MediaFolderController::class, 'restore'])->name('restore')->withTrashed();
        Route::put('/{folder}/move', [MediaFolderController::class, 'move'])->name('move');
        Route::post('/{folder}/share', [MediaFolderController::class, 'createShareLink'])->name('share');
    });

    // Tag operations
    Route::prefix('tags')->name('tags.')->group(function (): void {
        Route::get('/', [MediaTagController::class, 'index'])->name('index');
        Route::post('/', [MediaTagController::class, 'store'])->name('store');
        Route::delete('/{tag}', [MediaTagController::class, 'destroy'])->name('destroy');
        Route::post('/attach', [MediaTagController::class, 'attach'])->name('attach');
        Route::post('/detach', [MediaTagController::class, 'detach'])->name('detach');
    });

    // Shares (fine-grained user-to-user)
    Route::prefix('shares')->name('shares.')->group(function (): void {
        Route::get('/', [MediaShareController::class, 'index'])->name('index');
        Route::post('/', [MediaShareController::class, 'store'])->name('store');
        Route::delete('/{share}', [MediaShareController::class, 'destroy'])->name('destroy');
    });

    // Comments threaded
    Route::prefix('comments')->name('comments.')->group(function (): void {
        Route::get('/', [MediaCommentController::class, 'index'])->name('index');
        Route::post('/', [MediaCommentController::class, 'store'])->name('store');
        Route::put('/{comment}', [MediaCommentController::class, 'update'])->name('update');
        Route::delete('/{comment}', [MediaCommentController::class, 'destroy'])->name('destroy');
    });

    // Workflow
    Route::prefix('workflow')->name('workflow.')->group(function (): void {
        Route::post('/{file}/submit', [MediaWorkflowController::class, 'submit'])->name('submit');
        Route::post('/{file}/approve', [MediaWorkflowController::class, 'approve'])->name('approve');
        Route::post('/{file}/reject', [MediaWorkflowController::class, 'reject'])->name('reject');
        Route::post('/{file}/archive', [MediaWorkflowController::class, 'archive'])->name('archive');
    });

    // Edit locks
    Route::prefix('locks')->name('locks.')->group(function (): void {
        Route::post('/{file}/acquire', [MediaLockController::class, 'acquire'])->name('acquire');
        Route::post('/{file}/release', [MediaLockController::class, 'release'])->name('release');
        Route::get('/{file}/status', [MediaLockController::class, 'status'])->name('status');
    });

    // Cloud import
    Route::prefix('cloud')->name('cloud.')->group(function (): void {
        Route::get('/{provider}/auth', [CloudImportController::class, 'authorize'])->name('auth');
        Route::get('/{provider}/callback', [CloudImportController::class, 'callback'])->name('callback');
        Route::get('/{provider}/files', [CloudImportController::class, 'listFiles'])->name('files');
        Route::post('/{provider}/import', [CloudImportController::class, 'import'])->name('import');
    });

    // Folder templates
    Route::prefix('templates')->name('templates.')->group(function (): void {
        Route::get('/', [FolderTemplateController::class, 'index'])->name('index');
        Route::post('/', [FolderTemplateController::class, 'store'])->name('store');
        Route::post('/{template}/apply', [FolderTemplateController::class, 'apply'])->name('apply');
        Route::delete('/{template}', [FolderTemplateController::class, 'destroy'])->name('destroy');
    });

    // Embed code generator
    Route::post('/files/{file}/embed', [MediaEmbedController::class, 'generateEmbedCode'])->name('files.embed');

    // Content moderation
    Route::prefix('moderation')->name('moderation.')->group(function (): void {
        Route::get('/', [ModerationController::class, 'queue'])->name('queue');
        Route::post('/{file}/approve', [ModerationController::class, 'approve'])->name('approve');
        Route::post('/{file}/reject', [ModerationController::class, 'reject'])->name('reject');
    });
});

// WebDAV gateway (basic auth, no session required)
Route::middleware(['web', 'throttle:120,1'])
    ->prefix('webdav')
    ->name('media.webdav.')
    ->group(function (): void {
        Route::match(
            ['OPTIONS', 'PROPFIND', 'GET', 'PUT', 'DELETE', 'MKCOL'],
            '/{path?}',
            [WebDavController::class, 'handle']
        )->where('path', '.*')->name('handle');
    });

// Public folder share (token-based, throttled, no auth required)
Route::middleware(['web', 'throttle:60,1'])
    ->get('media/folder/share/{token}', [PublicMediaController::class, 'showSharedFolder'])
    ->name('media.folder.share');

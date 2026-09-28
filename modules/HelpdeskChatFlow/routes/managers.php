<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowAnalyticsController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowsController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowSessionsController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowTestCasesController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowTestController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowTransferController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowVersionsController;

Route::prefix('chatflows')
    ->name('chatflow.')
    ->middleware('integration.enabled:chatflow')
    ->group(function () {
        Route::get('/', [ChatFlowsController::class, 'index'])->name('index');
        Route::get('/create', [ChatFlowsController::class, 'create'])->name('create');
        Route::post('/', [ChatFlowsController::class, 'store'])->name('store');
        Route::post('/template/{template}', [ChatFlowsController::class, 'storeFromTemplate'])->name('store-template');
        Route::post('/import', [ChatFlowTransferController::class, 'import'])->name('import');
        Route::get('/{chatFlow}/export', [ChatFlowTransferController::class, 'export'])->name('export');
        Route::get('/{chatFlow}/versions', [ChatFlowVersionsController::class, 'index'])->name('versions');
        Route::get('/{chatFlow}/versions/{version}/diff', [ChatFlowVersionsController::class, 'diff'])->name('versions.diff');
        Route::post('/{chatFlow}/versions/{version}/restore', [ChatFlowVersionsController::class, 'restore'])->name('versions.restore');

        // Test scenarios (regression)
        Route::get('/{chatFlow}/test-cases', [ChatFlowTestCasesController::class, 'index'])->name('test-cases.index');
        Route::post('/{chatFlow}/test-cases', [ChatFlowTestCasesController::class, 'store'])->name('test-cases.store');
        Route::delete('/{chatFlow}/test-cases/{testCase}', [ChatFlowTestCasesController::class, 'destroy'])->name('test-cases.destroy');
        Route::post('/{chatFlow}/test-cases/{testCase}/run', [ChatFlowTestCasesController::class, 'run'])->name('test-cases.run');
        Route::post('/{chatFlow}/test-cases/run-all', [ChatFlowTestCasesController::class, 'runAll'])->name('test-cases.run-all');

        // Test panel routes fire the simulator, which can call OpenAI / external
        // HTTP — throttle to prevent runaway cost from a stuck or abusive client.
        Route::post('/test/send', [ChatFlowTestController::class, 'send'])
            ->middleware('throttle:60,1')->name('test.send');
        Route::post('/{chatFlow}/test/upload', [ChatFlowTestController::class, 'upload'])
            ->middleware('throttle:30,1')->name('test.upload');
        Route::get('/{chatFlow}/edit', [ChatFlowsController::class, 'edit'])->name('edit');
        Route::put('/{chatFlow}', [ChatFlowsController::class, 'update'])->name('update');
        Route::delete('/{chatFlow}', [ChatFlowsController::class, 'destroy'])->name('destroy');
        Route::post('/{chatFlow}/publish', [ChatFlowsController::class, 'publish'])->name('publish');
        Route::post('/{chatFlow}/duplicate', [ChatFlowsController::class, 'duplicate'])->name('duplicate');
        Route::get('/{chatFlow}/sessions', [ChatFlowSessionsController::class, 'index'])->name('sessions');
        Route::get('/{chatFlow}/sessions/{session}/replay', [ChatFlowSessionsController::class, 'replay'])->name('sessions.replay');
        Route::get('/{chatFlow}/analytics', [ChatFlowAnalyticsController::class, 'show'])->name('analytics');
        Route::post('/{chatFlow}/test/start', [ChatFlowTestController::class, 'start'])
            ->middleware('throttle:30,1')->name('test.start');

        // Supervisor takes over a bot-handled conversation (stops the bot + assigns to self).
        Route::post('/takeover/{conversationId}', [ChatFlowSessionsController::class, 'takeOver'])
            ->middleware('throttle:30,1')->name('takeover');
    });

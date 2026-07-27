<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowsController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowTestCasesController;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowTestController;

Route::prefix('chatflows')
    ->name('chatflow.')
    ->middleware('integration.enabled:chatflow')
    ->group(function () {
        Route::get('/', [ChatFlowsController::class, 'index'])->name('index');
        Route::get('/create', [ChatFlowsController::class, 'create'])->name('create');
        Route::post('/', [ChatFlowsController::class, 'store'])->name('store');
        Route::post('/template/{template}', [ChatFlowsController::class, 'storeFromTemplate'])->name('store-template');
        Route::post('/import', [ChatFlowsController::class, 'import'])->name('import');
        Route::get('/{chatFlow}/export', [ChatFlowsController::class, 'export'])->name('export');
        Route::get('/{chatFlow}/versions', [ChatFlowsController::class, 'versions'])->name('versions');
        Route::post('/{chatFlow}/versions/{version}/restore', [ChatFlowsController::class, 'restoreVersion'])->name('versions.restore');

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
        Route::get('/{chatFlow}/sessions', [ChatFlowsController::class, 'sessions'])->name('sessions');
        Route::get('/{chatFlow}/sessions/{session}/replay', [ChatFlowsController::class, 'replaySession'])->name('sessions.replay');
        Route::get('/{chatFlow}/analytics', [ChatFlowsController::class, 'analytics'])->name('analytics');
        Route::post('/{chatFlow}/test/start', [ChatFlowTestController::class, 'start'])
            ->middleware('throttle:30,1')->name('test.start');

        // Supervisor takes over a bot-handled conversation (stops the bot + assigns to self).
        Route::post('/takeover/{conversationId}', [ChatFlowsController::class, 'takeOver'])
            ->middleware('throttle:30,1')->name('takeover');
    });
